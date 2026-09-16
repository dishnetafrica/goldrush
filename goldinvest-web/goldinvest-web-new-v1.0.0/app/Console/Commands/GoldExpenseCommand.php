<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\TradingExpense;
use Illuminate\Console\Command;

/**
 * Records a cost against a deal. Until these are entered, a lot's profit is an
 * upper bound: transport, refining charges, assay, security and travel are real
 * money that came out of the same pot the profit is paid from.
 */
class GoldExpenseCommand extends Command
{
    private const CATEGORIES = [
        'transport', 'refining', 'assay', 'security', 'commission', 'travel', 'operating', 'other',
    ];

    protected $signature = 'gold:expense
                            {--lot= : lot code this cost belongs to}
                            {--date= : date paid, YYYY-MM-DD (default today)}
                            {--category=other : transport, refining, assay, security, commission, travel, operating or other}
                            {--description= : what it was for}
                            {--amount= : amount paid}
                            {--currency=USD : currency paid in}
                            {--fx=1 : units of that currency per 1 USD}
                            {--list : list the costs already recorded against the lot}';

    protected $description = 'Record a cost against a gold deal';

    public function handle(): int
    {
        $lot = null;

        if ($this->option('lot')) {
            $lot = GoldLot::where('lot_code', $this->option('lot'))->first();

            if (! $lot) {
                $this->error('No lot found with code ' . $this->option('lot'));

                return self::FAILURE;
            }
        }

        if ($this->option('list')) {
            return $this->listExpenses($lot);
        }

        $category = strtolower((string) $this->option('category'));
        if (! in_array($category, self::CATEGORIES, true)) {
            $this->error('--category must be one of: ' . implode(', ', self::CATEGORIES));

            return self::FAILURE;
        }

        $description = $this->option('description');
        if (! $description) {
            $this->error('Say what the cost was for: --description="Transport Juba to Nairobi"');

            return self::FAILURE;
        }

        $amount = (float) $this->option('amount');
        $fx = (float) $this->option('fx');

        if ($amount <= 0) {
            $this->error('--amount must be greater than zero.');

            return self::FAILURE;
        }

        if ($fx <= 0) {
            $this->error('--fx must be greater than zero.');

            return self::FAILURE;
        }

        $amountUsd = $amount / $fx;

        TradingExpense::create([
            'expense_date'   => $this->option('date') ?: now()->toDateString(),
            'category'       => $category,
            'description'    => $description,
            'currency_code'  => strtoupper((string) $this->option('currency')),
            'amount_local'   => $amount,
            'fx_rate_to_usd' => $fx,
            'amount_usd'     => $amountUsd,
            'gold_lot_id'    => $lot?->id,
        ]);

        $this->info('Recorded ' . number_format($amountUsd, 2) . ' USD of ' . $category
            . ($lot ? ' against ' . $lot->lot_code : ' (not tied to a lot)'));

        if ($lot) {
            $total = TradingExpense::where('gold_lot_id', $lot->id)->sum('amount_usd');
            $this->line('Costs on this deal so far: ' . number_format((float) $total, 2) . ' USD');
        }

        return self::SUCCESS;
    }

    private function listExpenses(?GoldLot $lot): int
    {
        $query = TradingExpense::query()->orderBy('expense_date');

        if ($lot) {
            $query->where('gold_lot_id', $lot->id);
        }

        $expenses = $query->get();

        if ($expenses->isEmpty()) {
            $this->warn('No costs recorded' . ($lot ? ' against ' . $lot->lot_code : '') . '.');
            $this->line('Profit for this deal is therefore an upper bound.');

            return self::SUCCESS;
        }

        $this->table(
            ['Date', 'Category', 'Description', 'Paid', 'USD'],
            $expenses->map(fn ($e) => [
                $e->expense_date->format('d M Y'),
                $e->category,
                $e->description,
                number_format((float) $e->amount_local, 2) . ' ' . $e->currency_code,
                number_format((float) $e->amount_usd, 2),
            ])->all()
        );

        $this->info('Total: ' . number_format((float) $expenses->sum('amount_usd'), 2) . ' USD');

        return self::SUCCESS;
    }
}
