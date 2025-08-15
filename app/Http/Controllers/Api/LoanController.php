<?php
namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Input;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\DB;
use App\{
    LoanOrder,
    LoanMechanism,
    Users
};

class LoanController extends Controller
{
    public function getCodeList(){
        $loanMechanism = DB::table('loan_mechanism')->select('currency_code') ->distinct() -> get();
        return $this->success(array(
            "loanMechanism" => $loanMechanism,
        ));
    }
    
    public function getBorroWingByCode(Request $request){
       $borrowingCode = $request->get('borrowing_code');
       $loanMechanism = LoanMechanism::where('currency_code',$borrowingCode) ->orderBy('day', 'asc') -> get();
       return $this->success(array(
            "loanMechanism" => $loanMechanism,
        ));
   }
   
   public function submit(Request $request){
       $user_id = Users::getUserId();
       $mechanism_id = $request->get('mechanism_id');
       $mechanism_name = $request->get('mechanism_name');
       $mechanism_day = $request->get('mechanism_day');
       $mechanism_rate = $request->get('mechanism_rate');
       $amount = $request->get('amount');
       $housing_img = $request->get('housing_img');
       $income_certificate_img = $request->get('income_certificate_img');
       $bank_details_img = $request->get('bank_details_img');
       $id_img = $request->get('id_img');
       $interest = $request->get('interest');
       $currency_code = $request->get('currency_code');
       if (empty($mechanism_id) || empty($mechanism_name) || empty($mechanism_day) || empty($mechanism_rate) || empty($amount) || empty($housing_img) || empty($income_certificate_img) || empty($bank_details_img) || empty($id_img) || empty($interest) || empty($currency_code)) {
            return $this->error('参数错误');
       }
       
       $loadOrder = new LoanOrder();
       $loadOrder -> user_id = $user_id;
       $loadOrder -> mechanism_id = $mechanism_id;
       $loadOrder -> mechanism_name = $mechanism_name;
       $loadOrder -> mechanism_day = $mechanism_day;
       $loadOrder -> mechanism_rate = $mechanism_rate;
       $loadOrder -> currency_code = $currency_code;
       $loadOrder -> amount = $amount;
       $loadOrder -> create_time = time();
       $loadOrder -> status = 0;
       $loadOrder -> housing_img = $housing_img;
       $loadOrder -> income_certificate_img = $income_certificate_img;
       $loadOrder -> bank_details_img = $bank_details_img;
       $loadOrder -> id_img = $id_img;
       $loadOrder -> interest = $interest;
       $loadOrder->save();
       return $this->success("提交申请成功,等待审核");
   }
   
   public function getOrderList(Request $request){
        $user_id = Users::getUserId();
        $limit = $request->get('limit', 10);
        $order_list = LoanOrder::where("user_id",$user_id) -> orderBy('id', 'desc')-> paginate($limit);
        return $this->success(array(
            "orderList" => $order_list,
            "list" => $order_list->items(), 'count' => $order_list->total(),
            "limit" => $limit,
        ));
   }
}
