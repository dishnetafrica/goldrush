<?php

namespace App\Console\Commands;

use App\Accounting\Services\ExpenseWorkflow;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\TradingExpense;
use Illuminate\Console\Command;

/**
 * Records a cost against a deal. Until these are entered, a lot's profit is an
 * upper bound: transport, refining charges, assay, security and travel are real
 * money that came out of the same pot the profit is paid from.
 *
 * What this raises is a draft claim, not a posting. It reaches the general
 * ledger once somebody has approved it, which is what the `expense` command
 * does.
 */
class GoldExpenseCommand extends Command
{
    private const CATEGORIES = TradingExpense::CATEGORIES;

    protected $signature = 'gold:expense
                            {--lot= : lot code this cost belongs to}
                            {--date= : date paid, YYYY-MM-DD (default today)}
                            {--category=other : one of transport, security, travel, refining, assay, packaging, storage, commission, other, operating}
                            {--description= : what it was for}
                            {--amount= : amount paid}
                            {--currency=USD : currency paid in}
                            {--fx=1 : units of that currency per 1 USD}
                            {--payee= : who was paid}
                            {--invoice= : the supplier\'s own invoice or receipt number}
                            {--capitalise : this cost prepares the gold for sale, so it belongs in inventory}
                            {--list : list the costs already recorded against the lot}';

    protected $description = 'Record a cost against a gold deal';

    public function handle(ExpenseWorkflow $workflow): int
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

        try {
            $expense = $workflow->draft([
                'expense_date'   => $this->option('date') ?: now()->toDateString(),
                'category'       => $category,
                'description'    => $description,
                'payee_name'     => $this->option('payee'),
                'external_ref'   => $this->option('invoice'),
                'currency_code'  => strtoupper((string) $this->option('currency')),
                'amount_local'   => $amount,
                'fx_rate_to_usd' => $fx,
                'capitalised'    => (bool) $this->option('capitalise'),
                'gold_lot_id'    => $lot?->id,
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Raised ' . $expense->reference . ': ' . number_format((float) $expense->amount_usd, 2)
            . ' USD of ' . $category . ($lot ? ' against ' . $lot->lot_code : ' (not tied to a deal)'));

        if ($expense->capitalised) {
            $this->line('Marked as capitalised: it will become part of what the gold cost, not an expense.');
        }

        $this->line('It is a draft. Next: expense submit ' . $expense->reference
            . ', then approve, then post.');

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
            ['Reference', 'Date', 'Category', 'Description', 'Paid', 'USD', 'Status', 'In'],
            $expenses->map(fn ($e) => [
                $e->reference ?? '#' . $e->id,
                $e->expense_date->format('d M Y'),
                $e->category,
                $e->description,
                number_format((float) $e->amount_local, 2) . ' ' . $e->currency_code,
                number_format((float) $e->amount_usd, 2),
                $e->status . ($e->payment_status === TradingExpense::PAYMENT_PAID ? ' / paid' : ''),
                $e->capitalised ? 'inventory' : 'expenses',
            ])->all()
        );

        // What the deal actually bears. A capitalised cost is in the gold's cost,
        // not in this total, and counting it here as well would be the double
        // count decision D4 exists to prevent.
        $counted = $expenses->filter(fn ($e) => $e->countsAsDealExpense());

        $this->info('Counted as deal expenses: ' . number_format((float) $counted->sum('amount_usd'), 2) . ' USD');

        $capitalised = $expenses->filter(fn ($e) => $e->countsInCostBasis());

        if ($capitalised->isNotEmpty()) {
            $this->line('Capitalised into the gold:  '
                . number_format((float) $capitalised->sum('amount_usd'), 2) . ' USD');
        }

        return self::SUCCESS;
    }
}
