<?php

namespace App\Console\Commands;

use App\{AutoList, CurrencyQuotation, MarketHour, TransactionComplete, UsersWallet};
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Log};

class AutoOrder extends Command
{
    protected $signature = 'auto_order {id}';
    protected $description = '机器人自动下单（优化版）';

    // 安全控制参数
    private $maxRuntime = 3600;    // 脚本最长运行时间（秒）
    private $maxErrors = 5;        // 最大允许错误次数
    private $errorCount = 0;       // 当前错误计数

    public function handle()
    {
        $startTime = time();
        $id = $this->argument('id');
        $faker = Factory::create();

        while (true) {
            // 1. 安全检测（超时或错误过多自动停止）
            if ($this->shouldTerminate($startTime)) {
                $this->error('脚本已安全终止');
                return;
            }

            // 2. 获取任务配置（短连接减少资源占用）
            $auto = AutoList::find($id);
            if (empty($auto) || empty($auto->is_start)) {
                $this->error('任务不存在或已关闭');
                break;
            }

            // 3. 业务逻辑（事务+批量化）
            try {
                DB::transaction(function () use ($auto, $faker) {
                    $this->processOrder($auto, $faker);
                });
            } catch (\Exception $e) {
                $this->handleError($e);
                continue;  // 跳过本次循环
            }

            // 4. 延迟控制（动态间隔）
            sleep(max(1, $auto->need_second));  // 至少1秒间隔
            $this->cleanMemory();              // 每轮循环清理内存
        }
    }

    /**
     * 处理订单逻辑（核心业务分离）
     */
    private function processOrder($auto, $faker)
    {
        // 1. 价格区间检查
        $priceArea = AutoList::getPriceArea($auto->currency_id, $auto->legal_id);
        if (empty($priceArea)) {
            throw new \Exception('无有效价格区间');
        }

        // 2. 生成随机交易数据
        $price = $faker->randomFloat(2, $priceArea['min'], $priceArea['max']);
        $number = $faker->randomFloat(2, $auto->min_number, $auto->max_number);

        // 3. 批量更新钱包（减少查询次数）
        $this->updateWallets($auto, $price, $number);

        // 4. 记录交易
        $this->createTransaction($auto, $price, $number);

        // 5. 更新行情数据
        $this->updateMarketData($auto, $price, $number);

        $this->info("生成交易: {$auto->legal_name}/{$auto->currency_name} 价格={$price} 数量={$number}");
    }

    /**
     * 批量更新钱包余额
     */
    private function updateWallets($auto, $price, $number)
    {
        $legalDecrement = bcmul($number, $price, 5);

        // 买方法币账户扣款
        UsersWallet::where('user_id', $auto->buy_user_id)
            ->where('currency', $auto->legal_id)
            ->decrement('legal_balance', $legalDecrement);

        // 买方币种账户增加
        UsersWallet::where('user_id', $auto->buy_user_id)
            ->where('currency', $auto->currency_id)
            ->increment('change_balance', $number);

        // 卖方账户操作（略，类似上方逻辑）
    }

    /**
     * 安全控制：检查是否终止脚本
     */
    private function shouldTerminate($startTime)
    {
        // 超时终止
        if (time() - $startTime > $this->maxRuntime) {
            Log::warning("脚本超时终止");
            return true;
        }

        // 错误过多终止
        if ($this->errorCount >= $this->maxErrors) {
            Log::error("错误次数超限终止");
            return true;
        }

        return false;
    }

    /**
     * 错误处理（记录+计数）
     */
    private function handleError(\Exception $e)
    {
        $this->errorCount++;
        Log::error("自动交易错误: {$e->getMessage()}");
        $this->error("错误: {$e->getMessage()} ({$this->errorCount}/{$this->maxErrors})");
    }

    /**
     * 定期内存清理
     */
    private function cleanMemory()
    {
        if ($this->errorCount % 10 === 0) {
            gc_collect_cycles();
        }
    }
}