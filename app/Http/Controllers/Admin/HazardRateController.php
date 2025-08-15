<?php

namespace App\Http\Controllers\Admin;

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

class HazardRateController extends Controller
{
    public function index()
    {
        $currencies = Currency::where('is_legal', 1)->get();
        return view('admin.lever.hazard.index')->with('currencies', $currencies);
    }

    public function handle()
    {
        return view('admin.lever.hazard.handle');
    }
    
    public function handle_kline(Request $request)
    {
        $id = $request->input('id', 0);
        $currency_info = CurrencyMatch::where('id', $id)->first();
        if(!$currency_info){
            return $this->error('参数错误');
        }
        $last_price = CurrencyQuotation::where('legal_id', $currency_info -> legal_id)
            ->where('currency_id', $currency_info -> currency_id)
            ->first();
        $total_fix = Db::table('kline_change') -> where("currency_id",$currency_info -> currency_id) -> where('legal_id', $currency_info -> legal_id) ->sum("fix");
        
        $last_price['sj'] = $last_price -> now_price - $total_fix;
        
        $result_now_price = bc_add($last_price -> now_price,$total_fix,5);
        
        $w_total_fix = Db::table('kline_change') -> where("currency_id",$currency_info -> currency_id) -> where('legal_id', $currency_info -> legal_id) -> where('status','0') ->sum("fix");
        $w_total_fix = bc_add($w_total_fix,0,5);
        $max_new_info = Db::table('kline_change') -> where("currency_id",$currency_info -> currency_id) -> where('legal_id', $currency_info -> legal_id) -> where('status','0') -> orderBy('id','desc' ) -> first();
        $flag = false;
        $sym = 0;
        if($max_new_info){
            $flag = true;
            $now = time();
            $created_at = $max_new_info -> created_at;
            $time1 = strtotime($created_at);
            $time1 = bcadd($time1,$max_new_info ->timer);
            $sym = $time1 - $now;
        }
        
        return view('admin.lever.hazard.handle_kline')->with('last_price', $last_price)->with('total_fix', bc_add($total_fix,0,5))->with('result_now_price', $result_now_price)->with('flag', $flag)->with('w_total_fix', $w_total_fix)->with('sym', $sym);
    }
    
    public function postHandleKline(Request $request){
        $id = $request->input('id', 0);
        $currency_info = CurrencyMatch::where('id', $id)->first();
        if(!$currency_info){
            return $this->error('参数错误');
        }
        $last_price = CurrencyQuotation::where('legal_id', $currency_info -> legal_id)
            ->where('currency_id', $currency_info -> currency_id)
            ->first();
        
        $validate_money = $last_price->now_price + $last_price->now_price * 0.1;
        
        
        $change = $request->input('change', 0);
        $result = $request->input('result', 0);
        $status = 0;
        if($result == 0){
            $status = 1;
        }
        
        $total_fix = Db::table('kline_change') -> where("currency_id",$currency_info -> currency_id) -> where('legal_id', $currency_info -> legal_id) ->sum("fix");
        
        
        
        $num1 = bc_add($last_price -> now_price,$change,5);
        $num2 = bc_add($num1,$total_fix,5);
        // var_dump($num2);
        if(round($validate_money,5) < $num2){
            return $this->error('最大调整不能超过10%');
        }
        
        DB::beginTransaction();
        try {
            
            $data = [
                'fix' => $change,
                'legal_id' => $currency_info -> legal_id,
            	'currency_id' => $currency_info -> currency_id,
            	'before_fixing' => $last_price -> now_price,
            	'after_modification' => $num2,
            	'timer' => $result,
            	'status' => $status,
            	'created_at' => date('Y-m-d H:i:s')
        	];
            Db::table('kline_change')->insert($data);
            $time = microtime(true);
            if($result == 0){
                
                $currency = Currency::find($currency_info -> currency_id);
                $legal = Currency::find($currency_info -> legal_id);
                // var_dump($currency->name);
                // var_dump($legal->name);
                // var_dump(intval($time));
                MarketHour::batchEsearchMarket($currency->name, $legal->name, $num2, intval($time));
                $result = CurrencyQuotation::getInstance($currency_info -> legal_id, $currency_info -> currency_id)->updateData(['now_price' => $num2]); //更新数据库价格
                if (!$result) {
                    throw new \Exception('更新每日价格失败');
                }
                LeverTransaction::newPrice($currency_info -> legal_id, $currency_info -> currency_id, $num2, $time);
            }
            
            DB::commit();
            return $this->success('设置成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
    
    public function getNowPrices(Request $request){
        $id = $request->input('id', 0);
        $currency_info = CurrencyMatch::where('id', $id)->first();
        if(!$currency_info){
            return $this->error('参数错误');
        }
        $last_price = CurrencyQuotation::where('legal_id', $currency_info -> legal_id)
            ->where('currency_id', $currency_info -> currency_id)
            ->first();
        return $this->success($last_price -> now_price);
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
    
    public function handle_new_post(Request $request)
    {
        $risk = $request->input('risk', 0);
        $zf = $request->input('zf', 0);
        $timer = $request->input('timer', 0);
        $id = $request->input('id', 0);
        $trade = LeverTransaction::where('status', LeverTransaction::TRANSACTION)->find($id);
        if (!$trade) {
            return $this->error('交易不存在或已平仓');
        }
        if($risk == 0){
            return $this->success("操作成功");
        }
        DB::beginTransaction();
        try {
            $lever_transaction = LeverTransaction::lockForupdate()->find($id);
            if (empty($lever_transaction)) {
                throw new \Exception("数据未找到");
            }
            $trade -> zf = $zf;
            $trade -> timer = $timer;
            $trade -> result = $risk;
            $trade -> save();
            DB::commit();
            return $this->success("操作成功");
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->error($ex->getMessage());
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

    public function lists(Request $request)
    {
        $limit = $request->input('limit', 10);
        $legal_id = $request->input('legal_id', -1);
        $type = $request->input('type', -1);
        $operate = $request->input('operate', -1);
        $hazard_rate = $request->input('hazard_rate', 0);
        $user_id = $request->input('user_id', 0);

        $user_hazard = LeverTransaction::where(function ($query) {
                $query->where('status',1)
                      ->orWhere('status',0);
            })->where(function ($query) use ($user_id, $type, $legal_id) {
                !empty($user_id) && $query->where('user_id1', $user_id);
                ($type != -1 && in_array($type, [1, 2])) && $query->where('type', $type);
                $legal_id != -1 && $query->where('legal', $legal_id);
            })
            ->orderBy('id', 'desc')
            ->paginate($limit);

        $items = $user_hazard->getCollection();
        $items->transform(function ($item, $key) use ($legal_id) {
            $user_wallet = UsersWallet::where('currency', $legal_id)
                ->where('user_id', $item->user_id)
                ->first();
            $sell_user = Users::where('id',$item->user_id)->first();
            // var_dump($item->user_id);
            // $user_info = DB::table('users')->where('id','=', $item->user_id)->select();
            $item->setAppends(['symbol', 'mobile', 'account_number', 'type_name', 'profits']);
            $hazard_rate = LeverTransaction::getWalletHazardRate($user_wallet);
            $balance = $user_wallet->lever_balance ?? 0;
            $item->setAttribute('lever_balance', $balance)->setAttribute('email', $sell_user)
                ->setAttribute('hazard_rate', $hazard_rate);
            return $item;
        });
        if ($operate != -1 && !empty($hazard_rate)) {
            switch ($operate) {
                case 1:
                    $operate_symbol = '>=';
                    break;
                case 2:
                    $operate_symbol = '<=';
                    break;
                default:
                    $operate_symbol = null;
                    break;
            }
            $items = $items->where('hazard_rate', $operate_symbol, $hazard_rate);
        }
        $user_hazard->setCollection($items);
        return $this->layuiData($user_hazard);
    }

    public function total()
    {
        $currencies = Currency::where('is_legal', 1)->get();
        return view('admin.lever.hazard.total')->with('currencies', $currencies);
    }

    public function totalLists(Request $request)
    {
        $limit = $request->input('limit', 10);
        $legal_id = $request->input('legal_id', -1);
        $type = $request->input('type', -1);
        $operate = $request->input('operate', -1);
        $hazard_rate = $request->input('hazard_rate', 0);
        /*
        SELECT
            `user_id`,
            SUM((case `type` when 1 then update_price-price when 2 then price-update_price END)) AS profits_total,
            SUM(caution_money) AS caution_money_total
        FROM lever_transaction
        WHERE `status`=0
        GROUP BY user_id
         */
        if($legal_id == -1){
            $legal_id=Currency::where('name','USDT')->first()->id??-1;
        }
        $user_hazard = LeverTransaction::where('status', LeverTransaction::TRANSACTION)
            ->where(function ($query) use ($type, $legal_id) {
                ($type != -1 && in_array($type, [1, 2])) && $query->where('type', $type);
                $legal_id != -1 && $query->where('legal', $legal_id);
            })
            ->select('user_id')
            ->selectRaw('SUM((CASE `type` WHEN 1 THEN `update_price` - `price` WHEN 2 THEN `price` - `update_price` END) * `number` * `multiple`) AS `profits_total`')
            ->selectRaw('SUM(`caution_money`) AS `caution_money_total`')
            ->groupBy('user_id')
            ->paginate($limit);
        $items = $user_hazard->getCollection();
        $items->transform(function ($item, $key) use ($legal_id) {
            $user_wallet = UsersWallet::where('currency', $legal_id)
                ->where('user_id', $item->user_id)
                ->first();
            $item->setAppends(['mobile', 'account_number']);
            $hazard_rate = LeverTransaction::getWalletHazardRate($user_wallet);
            $balance = $user_wallet->lever_balance ?? 0;
            $item->setAttribute('lever_balance', $balance)
                ->setAttribute('hazard_rate', floatval($hazard_rate));
            return $item;
        });
        if ($operate != -1 && !empty($hazard_rate)) {
            switch ($operate) {
                case 1:
                    $operate_symbol = '>=';
                    break;
                case 2:
                    $operate_symbol = '<=';
                    break;
                default:
                    $operate_symbol = null;
                    break;
            }
            $items = $items->where('hazard_rate', $operate_symbol, $hazard_rate);
        }
        $user_hazard->setCollection($items);
        return $this->layuiData($user_hazard);
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
