<?php

namespace App\Jobs\User;

use App\Constants\GlobalConst;
use App\Constants\PaymentGatewayConst;
use App\Models\InvestmentProfitLog;
use App\Notifications\User\InvestmentProfitNofication;
use App\Notifications\User\InvestmentProfitNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InvestmentProfitDistribute implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $task;
    public $user;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $task)
    {
        $this->task = $task;
        $this->user = $task['user'];
    }

    /**
     * Get task data to array
     * @return array
     */
    public function getTaskData()
    {
        return $this->task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->crateProfit()) {
            try {
                Notification::route('mail', $this->user->email)->notify(new InvestmentProfitNotification($this->task));
            } catch (Exception $e) {
                // Handle Error
            }
        } else {
            throw new Exception("Something went wrong! Failed to create profit log");
        }
    }

    /**
     * Ready to insert data as per model requirement
     */
    public function insertData()
    {
        return [
            'user_id'       => $this->task['user_id'],
            'wallet_id'     => $this->task['profit_wallet']->id,
            'invest_id'     => $this->task['invest_id'],
            'profit_amount' => $this->task['total_profit'],
            'created_at'    => now(),
        ];
    }

    /**
     * Insert profit log
     */
    public function crateProfit()
    {
        DB::beginTransaction();
        try {

            if ($this->task['action_type'] == GlobalConst::PROFIT) {
                DB::table('investment_profit_logs')->insert($this->insertData());
                DB::table($this->task['profit_wallet']->getTable())->where('id', $this->task['profit_wallet']->id)->increment('profit_balance', $this->task['total_profit']);
            } else if ($this->task['action_type'] == GlobalConst::INVESTMENT) {
                // Return back investment capital
                $this->createCapitalReturnTransaction();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return true;
    }

    /**
     * Create a new transaction for return capital in user wallet
     */
    public function createCapitalReturnTransaction()
    {
        DB::table('transactions')->insert([
            'type'                          => PaymentGatewayConst::TYPECAPITALRETURN,
            'trx_id'                        => generate_unique_string('transactions', 'trx_id', 16),
            'user_type'                     => GlobalConst::USER,
            'wallet_id'                     => $this->task['profit_wallet']->id,
            'user_id'                       => $this->task['user_id'],
            'payment_gateway_currency_id'   => null,
            'request_amount'                => $this->task['invest_amount'],
            'request_currency'              => $this->task['profit_wallet']->currency->code,
            'exchange_rate'                 => 0,
            'percent_charge'                => 0,
            'fixed_charge'                  => 0,
            'total_charge'                  => 0,
            'total_payable'                 => $this->task['invest_amount'],
            'receive_amount'                => $this->task['invest_amount'],
            'receiver_type'                 => GlobalConst::USER,
            'receiver_id'                   => $this->task['user_id'],
            'available_balance'             => $this->task['profit_wallet']->balance,
            'payment_currency'              => $this->task['profit_wallet']->currency->code,
            'remark'                        => "Capital Return (" . $this->task['investment']->investPlan->name . ")",
            'status'                        => PaymentGatewayConst::STATUSSUCCESS,
            'created_at'                    => now(),
        ]);

        DB::table($this->task['profit_wallet']->getTable())->where('id', $this->task['profit_wallet']->id)->increment('balance', $this->task['invest_amount']);

        // update investment as complete
        DB::table($this->task['investment']->getTable())->where('id',$this->task['investment']->id)->update(['status' => GlobalConst::COMPLETE]);
    }
}
