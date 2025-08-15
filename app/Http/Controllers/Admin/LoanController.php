<?php

namespace App\Http\Controllers\Admin;

use App\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\{
    LoanOrder,
    LoanMechanism,
    Setting,
    Currency,
    UsersWallet,
    AccountLog
};
use App\Utils\Hash;

class LoanController extends Controller
{
    public function order()
    {
        $imageServerUrl = Setting::getValueByKey('image_server_url', '');
        return view("admin.loan.order",['imageServerUrl' => $imageServerUrl]);
    }
    
    public function order_list(Request $request){
        $limit = $request->get('limit');
        $userId = $request->get('user_id');
        $start_time = strtotime($request->get('start_time', 0));
        $end_time = strtotime($request->get('end_time', 0));
        
        $list = LoanOrder::where(function ($query) use ($start_time) {
            if (!empty($start_time)) {
                 $query->where('create_time', '>=', $start_time);
            }
        })->where(function ($query) use ($end_time) {
            if ($end_time != '') {
                $query->where('create_time', '<=', $end_time);
            }
        })->where(function ($query) use ($userId) {
            if ($userId != '') {
                $query->where('user_id', '=', $userId);
            }
        })->orderBy('id', 'desc')->paginate($limit);
        
        return $this->layuiData($list);
    }
    
     public function mechanism(){
         return view("admin.loan.mechanism");
     }
     
     public function mechanism_list(Request $request){
        $limit = $request->get('limit');
        $start_time = strtotime($request->get('start_time', 0));
        $end_time = strtotime($request->get('end_time', 0));
        
        $list = LoanMechanism::where(function ($query) use ($start_time) {
            if (!empty($start_time)) {
                 $query->where('create_time', '>=', $start_time);
            }
        })->where(function ($query) use ($end_time) {
            if ($end_time != '') {
                $query->where('create_time', '<=', $end_time);
            }
        })->orderBy('id', 'desc')->paginate($limit);
        
        return $this->layuiData($list);
    }
    
    public function mechanism_add(Request $request){
        $id = $request->get('id');
        if ($id){
            $loanMechanism = LoanMechanism::where('id',$id)->first();
        }else{
            $loanMechanism = new LoanMechanism();
        }
        return view("admin.loan.mechanism_add",[ 'loan_mechanism' => $loanMechanism]);
    }
    
    public function mechanism_post(Request $request){
        $id = $request->get('id');
        $name = $request->get('name');
        $min = $request->get('min');
        $rate = $request->get('rate');
        $day = $request->get('day');
        $max = $request->get('max');
        $currency_code = $request->get('currency');
        
        if (empty($name) || empty($min) || empty($rate) || empty($day) || empty($max)){
            return $this->error('请完善机构信息');
        }
        if ($id){
            $loanMechanism = LoanMechanism::where('id',$id)->first();
        }else{
            $loanMechanism = new LoanMechanism();
        }
        $loanMechanism->day = $day;
        $loanMechanism->rate = $rate;
        $loanMechanism->name = $name;
        $loanMechanism->min = $min;
        $loanMechanism->max = $max;
        $loanMechanism->currency_code = $currency_code;
        $loanMechanism->save();
        $msg = $id ? '修改成功' : '添加成功';
        return $this->success($msg);
    }
    
    public function mechanism_del(Request $request){
        $loanMechanism = LoanMechanism::find(Input::get('id'));
        if($loanMechanism == null) {
            abort(404);
        }
        $bool = $loanMechanism->delete();
        if($bool){
            return $this->success('删除成功');
        }else{
            return $this->error('删除失败');
        }
    }
    
    public function order_pass(Request $request){
        $loanOrder = LoanOrder::find(Input::get('id'));
        if($loanOrder == null) {
             return $this->error('参数错误');
        }
        $currency = Currency::where("name",$loanOrder -> currency_code) ->first();
        
        $legal = UsersWallet::where("user_id", $loanOrder->user_id)
            ->where("currency", $currency->id)
            ->lockForUpdate()
            ->first();
		if(!$legal){
		    return $this->error('找不到用户钱包');
        }
        DB::beginTransaction();
        try{
            change_wallet_balance(
                $legal,
                2,
                $loanOrder->amount,
                AccountLog::ADMIN_CHANGE_BALANCE,
                '贷款',
                false,
                0,
                0,
                serialize([
                ]),
                false,
                true
            );
            $loanOrder -> status = 1;
            $loanOrder -> update_time = time();
            $loanOrder ->save();
            DB::commit();
        }catch (\Exception $e){
		    DB::rollBack();
		    return $this->error($e->getMessage());
        }
		return $this->success('操作成功');
    }
    
    public function order_nopass(Request $request){
        $loanOrder = LoanOrder::find(Input::get('id'));
        $value = Input::get('value');
        if($loanOrder == null || empty($value)) {
             return $this->error('参数错误');
        }
        $loanOrder -> status = 2;
        $loanOrder -> update_time = time();
        $loanOrder -> common = $value;
        $loanOrder -> save();
        return $this->success('操作成功');
    }
}