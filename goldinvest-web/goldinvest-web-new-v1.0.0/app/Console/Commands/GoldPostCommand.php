<?php

namespace App\Console\Commands;

use App\Accounting\Models\CashAccount;
use App\Accounting\Services\GoldTradingPoster;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Models\TradingExpense;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * Puts a gold trading record into the company's books.
 */
class GoldPostCommand extends Command
{
    protected $signature = 'gold:post
                            {what : purchase|refining|sale|expense}
                            {ref : lot code, processing id, sale code or expense id}
                            {--from= : cash account the money was paid from}
                            {--to= : cash account sale proceeds arrived in}
                            {--date= : posting date, defaults to the record\'s own date}
                            {--memo= }';

    protected $description = 'Post a gold purchase, refining, sale or expense to the general ledger';

    public function handle(GoldTradingPoster $poster): int
    {
        $context = array_filter([
            'date' => $this->option('date'),
            'memo' => $this->option('memo'),
        ]);

        try {
            $journal = match ($this->argument('what')) {
                'purchase' => $poster->purchase($this->lot(), $this->cashAccount('from'), $context),
                'refining' => $poster->refine(
                    GoldProcessing::with('lot')->findOrFail((int) $this->argument('ref')),
                    $this->cashAccount('from'), $context
                ),
                'sale' => $poster->sell(
                    GoldSale::with('lot')->where('sale_code', $this->argument('ref'))->firstOrFail(),
                    $this->cashAccount('to'), $context
                ),
                'expense' => $poster->expense(
                    TradingExpense::with('lot')->findOrFail((int) $this->argument('ref')),
                    $this->cashAccount('from'), $context
                ),
                default => throw new \InvalidArgumentException('What must be purchase, refining, sale or expense.'),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Posted ' . $journal->reference . ' - ' . $journal->memo);
        $this->table(
            ['Account', 'Debit', 'Credit', 'Memo'],
            $journal->lines->map(fn ($l) => [
                $l->account->label(),
                $l->debit > 0 ? Money::format($l->debit) : '',
                $l->credit > 0 ? Money::format($l->credit) : '',
                $l->memo,
            ])->all()
        );

        return self::SUCCESS;
    }

    private function lot(): GoldLot
    {
        return GoldLot::where('lot_code', $this->argument('ref'))->firstOrFail();
    }

    private function cashAccount(string $option): ?CashAccount
    {
        if (! $this->option($option)) {
            return null;
        }

        return CashAccount::where('code', $this->option($option))->firstOrFail();
    }
}
