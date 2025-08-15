<?php
/**
 * Created by PhpStorm.
 * User: YSX
 * Date: 2018/12/4
 * Time: 17:17
 */

namespace App\Http\Controllers\Agent;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\LeverTransaction;
use App\UsersWallet;
use App\Currency;
use App\CurrencyMatch;
use App\MarketHour;
use App\CurrencyQuotation;
use App\Users;
use App\AccountLog;

class LeverTransactionController extends Controller
{

    public function lists()
    {
        $user_id = request()->input('user_id', 0);
        $status  = request()->input('status', -1);
        $type    = request()->input('type', -1);

        $where = [];

        $where[] = ['user_id',$user_id];
        if ($status!=-1) $where[] = ['status', $status];
        if ($type!=-1) $where[] = ['type', $type];

        $list = LeverTransaction::where($where)->orderBy('id', 'DESC')->paginate();
        return $this->layuiData($list);
    }
    
    public function postHandle_y_new(Request $request){
        $id = $request->input('id', 0);
        $trade = LeverTransaction::where('status', LeverTransaction::TRANSACTION)->find($id);
        if (!$trade) {
            return $this->error('交易不存在或已平仓');
        }
        
        $currency_id = $trade->currency;
       $legal_id = $trade->legal;
       $time = microtime(true);
       $update_price = $trade -> target_profit_price;
        
        DB::beginTransaction();
        try {
            //MarketHour::batchWriteMarketData($currency_id, $legal_id, 0, $update_price, 3, intval($time));
           
            
            LeverTransaction::newPrice($legal_id, $currency_id, $update_price, $time);
            DB::commit();
            return $this->success('向系统发送价格成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
    
    public function postHandle_c_new(Request $request){
        $id = $request->input('id', 0);
        $trade = LeverTransaction::where('status', LeverTransaction::TRANSACTION)->find($id);
        if (!$trade) {
            return $this->error('交易不存在或已平仓');
        }
        
        $currency_id = $trade->currency;
       $legal_id = $trade->legal;
       $time = microtime(true);
       $update_price = $trade -> stop_loss_price;
        
        DB::beginTransaction();
        try {
            //MarketHour::batchWriteMarketData($currency_id, $legal_id, 0, $update_price, 3, intval($time));
           
            
            LeverTransaction::newPrice($legal_id, $currency_id, $update_price, $time);
            DB::commit();
            return $this->success('向系统发送价格成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
    
   
    
    public function postHandle_new(Request $request)
    {
        $trade_id = $request->input('id', 0);
        DB::beginTransaction();
        try {
            $lever_transaction = LeverTransaction::lockForupdate()->find($trade_id);
            if (empty($lever_transaction)) {
                throw new \Exception("数据未找到");
            }
           
            if ($lever_transaction->status != LeverTransaction::TRANSACTION) {
                throw new \Exception("交易状态异常,请勿重复提交");
            }
            if ($lever_transaction->order_type == 2) {  //跟随的订单禁止主动平仓
                throw new \Exception("无权操作");
            }
            $return = LeverTransaction::leverClose($lever_transaction);
            if (!$return) {
                throw new \Exception("平仓失败,请重试");
            }
            if($lever_transaction->origin_price <= 0){
                throw new \Exception("交易异常，无法平仓");
            }
            DB::commit();
            return $this->success("操作成功");
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->error($ex->getMessage());
        }
    }

    public function postHandle(Request $request)
    {
        $trade_id = $request->input('id', 0);
        $update_price = $request->input('update_price', 0);
        $write_market = $request->input('write_market', 0);
        if ($trade_id <= 0 || $update_price <= 0) {
            return $this->error('参数不合法');
        }
        $time = microtime(true);
        $trade = LeverTransaction::where('status', LeverTransaction::TRANSACTION)->find($trade_id);
        if (!$trade) {
            return $this->error('交易不存在或已平仓');
        }
        $legal_id = $trade->legal;
        $legal = Currency::find($legal_id);
        $currency_id = $trade->currency;
        $currency = Currency::find($currency_id);
        DB::beginTransaction();
        try {
            //MarketHour::batchWriteMarketData($currency_id, $legal_id, 0, $update_price, 3, intval($time));
           if ($write_market) {
               
                MarketHour::batchEsearchMarket($currency->name, $legal->name, $update_price, intval($time)); //更新esearch行情价格
                $result = CurrencyQuotation::getInstance($legal_id, $currency_id)->updateData(['now_price' => $update_price]); //更新数据库价格
                if (!$result) {
                    throw new \Exception('更新每日价格失败');
                }
            }
            
            LeverTransaction::newPrice($legal_id, $currency_id, $update_price, $time);
            DB::commit();
            return $this->success('向系统发送价格成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
    
    public function postHandle_new1(Request $request)
    {
        $trade_id = $request->input('id', 0);
        $lever_transaction = LeverTransaction::lockForupdate()->find($trade_id);
        if (empty($lever_transaction)) {
            throw new \Exception("数据未找到");
        }
        $user_id = $lever_transaction->user_id;
        $id = $trade_id;
        try {
            //退手续费和保证金
            DB::transaction(function () use ($user_id, $id) {
                $lever_trade = LeverTransaction::where('user_id', $user_id)
                    ->where('status', LeverTransaction::ENTRUST)
                    ->lockForUpdate()
                    ->find($id);
                if (!$lever_trade) {
                    throw new \Exception('交易不存在或已撤单,请刷新后重试');
                }
                $legal_id = $lever_trade->legal;
                $refund_trade_fee = $lever_trade->trade_fee;
                $refund_caution_money = $lever_trade->caution_money;
                $legal_wallet = UsersWallet::where('user_id', $user_id)
                    ->where('currency', $legal_id)
                    ->first();
                if (!$legal_wallet) {
                    throw new \Exception('撤单失败:用户钱包不存在');
                }
                $result = change_wallet_balance(
                    $legal_wallet,
                    3,
                    $refund_trade_fee,
                    AccountLog::LEVER_TRANSACTIO_CANCEL,
                    '杠杆' . $lever_trade->type_name . '委托撤单,退回手续费',
                    false,
                    0,
                    0,
                    '',
                    true
                );
                if ($result !== true) {
                    throw new \Exception($this->returnStr('撤单失败:') . $result);
                }
                $result = change_wallet_balance(
                    $legal_wallet,
                    3,
                    $refund_caution_money,
                    AccountLog::LEVER_TRANSACTIO_CANCEL,
                    '杠杆' . $lever_trade->type_name . '委托撤单,退回保证金',
                    false,
                    0,
                    0,
                    '',
                    true
                );
                if ($result !== true) {
                    throw new \Exception($this->returnStr('撤单失败:') . $result);
                }
                $lever_trade->status = LeverTransaction::CANCEL;
                $lever_trade->complete_time = time();
                $result = $lever_trade->save();
                if (!$result) {
                    throw new \Exception('撤单失败:变更状态失败');
                }
            });
            return $this->success('撤单成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

}