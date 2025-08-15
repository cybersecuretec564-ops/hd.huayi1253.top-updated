<?php

namespace App\Console\Commands;

use App\Market;
use App\Utils\RPC;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Currency;
use App\MarketHour;
use App\CurrencyQuotation;
use App\LeverTransaction;
class KlineChanageWrite extends Command
{
	protected $signature = "kline_chanage_write";
	protected $description = "动态写入行情";
	public function __construct()
	{
		parent::__construct();
	}
	public function handle()
	{
	    $klines = Db::table('kline_change') -> where('status',0) -> get();
	    foreach($klines as $kline){
	        $now = time();
	        $time = microtime(true);
	        $created_at = $kline -> created_at;
            $time1 = strtotime($created_at);
            $time1 = bcadd($time1,$kline ->timer);
            $sym = $time1 - $now;
            if($sym <= 0){
                DB::beginTransaction();
                try {
                    $currency = Currency::find($kline -> currency_id);
                    $legal = Currency::find($kline -> legal_id);
                    MarketHour::batchEsearchMarket($currency->name, $legal->name, $kline ->after_modification, intval($time));
                    $result = CurrencyQuotation::getInstance($kline -> legal_id, $kline -> currency_id)->updateData(['now_price' => $kline ->after_modification]); //更新数据库价格
                    if (!$result) {
                        throw new \Exception('更新每日价格失败');
                    }
                    LeverTransaction::newPrice($kline -> legal_id, $kline -> currency_id, $kline ->after_modification, $time);
                    DB::table('kline_change')->where('id', $kline -> id)->update(['status' => 1]);
                    DB::commit();
                    $this->info('设置成功');
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->info('失败'.$e->getMessage());
                }
            }
	    }
	    $this->info('监控中');
	    
	}
}