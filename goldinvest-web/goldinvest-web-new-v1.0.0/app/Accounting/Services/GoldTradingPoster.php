<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\Journal;
use App\Accounting\Security\AccountingPermission;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Models\LotResult;
use App\GoldTrading\Models\TradingExpense;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;

/**
 * Puts the gold trading business into the company's books.
 *
 * Buying gold turns cash into an asset. Refining moves that asset between two
 * inventory accounts and loses grams without losing dollars. Selling it earns
 * revenue and charges the cost of the grams that left. Deal costs are expenses
 * of the period they fall in.
 *
 * Every one of those is a journal posted through JournalPoster, so the rules
 * that hold everywhere hold here too: it balances or it is refused, and once
 * posted it cannot be edited.
 *
 * Nothing in here touches an investor balance. Gold is a company asset and
 * investor money is a company liability; the two meet only at a period-close
 * distribution, which is 3F.
 */
class GoldTradingPoster
{
    private const EPSILON = 0.00000001;

    /** Where each kind of deal cost lands in the chart of accounts. */
    private const EXPENSE_ACCOUNTS = [
        'transport'  => '6000',
        'refining'   => '6010',
        'assay'      => '6020',
        'security'   => '6030',
        'travel'     => '6040',
        'commission' => '6050',
        'operating'  => '6100',
        'other'      => '6100',
    ];

    public const PAYABLE    = '2100';
    public const RECEIVABLE = '1300';
    public const REVENUE    = '4000';
    public const COGS       = '5000';

    public function __construct(
        private readonly JournalPoster $poster,
        private readonly CashService $cash,
        private readonly InventoryValuation $inventory,
    ) {
    }

    /**
     * Gold bought. Cash becomes inventory; the company is no richer or poorer
     * for it, which is why no revenue or expense account is touched.
     */
    public function purchase(GoldLot $lot, ?CashAccount $from = null, array $context = [], ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::JOURNAL_POST);
        $this->assertNotHistorical($lot);

        if ($lot->purchase_journal_id !== null) {
            throw new PostingRefused('Lot ' . $lot->lot_code . ' has already been posted as journal #' . $lot->purchase_journal_id . '.');
        }

        $amount = round((float) $lot->total_cost_usd, 8);

        if ($amount <= self::EPSILON) {
            throw new PostingRefused('Lot ' . $lot->lot_code . ' has no cost to post.');
        }

        $date = $context['date'] ?? $lot->purchase_date->toDateString();

        if ($from) {
            $this->cash->assertCanPay($from, $amount, $date);
        }

        return DB::transaction(function () use ($lot, $from, $amount, $date, $context, $actor) {
            $journal = $this->poster->post([
                [
                    'account'     => InventoryValuation::UNREFINED,
                    'debit'       => $amount,
                    'gold_lot_id' => $lot->id,
                    'memo'        => number_format((float) $lot->gross_grams, 4) . ' g at '
                        . Money::format((float) $lot->price_per_gram_usd) . '/g',
                ],
                [
                    'account' => $from ? $from->glAccount : self::PAYABLE,
                    'credit'  => $amount,
                    'memo'    => $from ? 'paid from ' . $from->label() : 'unpaid at purchase',
                ],
            ], [
                'date'        => $date,
                'memo'        => $context['memo'] ?? ('Gold purchase ' . $lot->lot_code),
                'source_type' => 'gold_lot',
                'source_id'   => $lot->id,
            ], $actor);

            $lot->forceFill([
                'purchase_journal_id'       => $journal->id,
                'paid_from_cash_account_id' => $from?->id,
            ])->save();

            return $journal;
        });
    }

    /**
     * Gold refined.
     *
     * Two things happen and only one of them is money. The cost of the gold
     * moves from unrefined to refined inventory unchanged; the grams lost
     * produce no journal at all, because nothing was spent to lose them. Any
     * charge for the refining is added to the cost of what came out.
     */
    public function refine(GoldProcessing $processing, ?CashAccount $from = null, array $context = [], ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::JOURNAL_POST);

        $lot = $processing->lot;
        $this->assertNotHistorical($lot);

        if ($processing->journal_id !== null) {
            throw new PostingRefused('Processing #' . $processing->id . ' has already been posted.');
        }

        if ($lot->purchase_journal_id === null) {
            throw new PostingRefused('Post the purchase of ' . $lot->lot_code . ' before its refining.');
        }

        // Whatever cost is still sitting in unrefined inventory for this lot is
        // what moves across.
        $reclass = $this->inventory->glBalance($lot, InventoryValuation::UNREFINED);

        if ($reclass <= self::EPSILON) {
            throw new PostingRefused(
                'There is nothing left in unrefined inventory for ' . $lot->lot_code . ' to refine.'
            );
        }

        $cost = $processing->cost_capitalised ? round((float) $processing->cost_usd, 8) : 0.0;
        $date = $context['date'] ?? $processing->processed_at->toDateString();

        if ($cost > self::EPSILON && $from) {
            $this->cash->assertCanPay($from, $cost, $date);
        }

        $lines = [
            [
                'account'     => InventoryValuation::REFINED,
                'debit'       => round($reclass + $cost, 8),
                'gold_lot_id' => $lot->id,
                'memo'        => number_format((float) $processing->output_grams, 4) . ' g out, '
                    . number_format((float) $processing->waste_grams, 4) . ' g lost',
            ],
            [
                'account'     => InventoryValuation::UNREFINED,
                'credit'      => $reclass,
                'gold_lot_id' => $lot->id,
                'memo'        => 'cost carried across; the grams lost cost nothing to lose',
            ],
        ];

        if ($cost > self::EPSILON) {
            $lines[] = [
                'account' => $from ? $from->glAccount : self::PAYABLE,
                'credit'  => $cost,
                'memo'    => 'refining charge capitalised into the gold',
            ];
        }

        return DB::transaction(function () use ($processing, $lot, $lines, $date, $context, $from, $actor) {
            $journal = $this->poster->post($lines, [
                'date'        => $date,
                'memo'        => $context['memo'] ?? ('Refining ' . $lot->lot_code),
                'source_type' => 'gold_processing',
                'source_id'   => $processing->id,
            ], $actor);

            $processing->forceFill([
                'journal_id'                => $journal->id,
                'paid_from_cash_account_id' => $from?->id,
            ])->save();

            return $journal;
        });
    }

    /**
     * Gold sold.
     *
     * One journal carries both halves of a sale: the money coming in against
     * revenue, and the cost of the grams that left against inventory. Splitting
     * them across two journals would allow a sale to exist with no cost, which
     * is how a set of books starts flattering itself.
     */
    public function sell(GoldSale $sale, ?CashAccount $to = null, array $context = [], ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::JOURNAL_POST);

        $lot = $sale->lot;
        $this->assertNotHistorical($lot);

        if ($sale->journal_id !== null) {
            throw new PostingRefused('Sale ' . $sale->sale_code . ' has already been posted.');
        }

        if ($lot->purchase_journal_id === null) {
            throw new PostingRefused('Post the purchase of ' . $lot->lot_code . ' before its sales.');
        }

        $proceeds = round((float) $sale->gross_proceeds_usd, 8);
        $valuation = $this->inventory->forLot($lot);
        $held = $this->inventory->glBalance($lot, InventoryValuation::REFINED);

        $grams = round((float) $sale->grams_sold, 4);
        $soldOut = round($valuation['remaining_grams'], 4) <= 0;

        // A sale that empties the lot takes whatever cost is left, so nothing is
        // stranded in inventory by rounding.
        $cogs = $soldOut ? $held : round($valuation['cost_per_refined_gram'] * $grams, 8);

        if ($cogs - $held > self::EPSILON) {
            throw new PostingRefused(
                'Selling ' . number_format($grams, 4) . ' g of ' . $lot->lot_code . ' would charge '
                . Money::format($cogs) . ' to cost of sales, but only ' . Money::format($held)
                . ' of that lot is in inventory. Inventory cannot go negative.'
            );
        }

        $date = $context['date'] ?? $sale->sale_date->toDateString();

        $lines = [
            [
                'account' => $to ? $to->glAccount : self::RECEIVABLE,
                'debit'   => $proceeds,
                'memo'    => $to ? 'received into ' . $to->label() : 'due from ' . ($sale->buyer_name ?? 'buyer'),
            ],
            [
                'account'     => self::REVENUE,
                'credit'      => $proceeds,
                'gold_lot_id' => $lot->id,
                'memo'        => number_format($grams, 4) . ' g at '
                    . Money::format((float) $sale->price_per_gram_usd) . '/g',
            ],
            [
                'account'     => self::COGS,
                'debit'       => $cogs,
                'gold_lot_id' => $lot->id,
                'memo'        => 'cost of ' . number_format($grams, 4) . ' g at '
                    . Money::format($valuation['cost_per_refined_gram']) . '/g',
            ],
            [
                'account'     => InventoryValuation::REFINED,
                'credit'      => $cogs,
                'gold_lot_id' => $lot->id,
                'memo'        => 'gold leaving inventory',
            ],
        ];

        return DB::transaction(function () use ($sale, $lot, $lines, $date, $context, $to, $actor) {
            $journal = $this->poster->post($lines, [
                'date'        => $date,
                'memo'        => $context['memo'] ?? ('Gold sale ' . $sale->sale_code . ' from ' . $lot->lot_code),
                'source_type' => 'gold_sale',
                'source_id'   => $sale->id,
            ], $actor);

            $sale->forceFill([
                'journal_id'                 => $journal->id,
                'proceeds_to_cash_account_id' => $to?->id,
            ])->save();

            return $journal;
        });
    }

    /** A cost of doing the deal: an expense of the period, not part of the gold. */
    public function expense(TradingExpense $expense, ?CashAccount $from = null, array $context = [], ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::JOURNAL_POST);

        if ($expense->journal_id !== null) {
            throw new PostingRefused('Expense #' . $expense->id . ' has already been posted.');
        }

        if ($expense->lot) {
            $this->assertNotHistorical($expense->lot);
        }

        $amount = round((float) $expense->amount_usd, 8);

        if ($amount <= self::EPSILON) {
            throw new PostingRefused('Expense #' . $expense->id . ' has no amount to post.');
        }

        $account = self::EXPENSE_ACCOUNTS[$expense->category] ?? self::EXPENSE_ACCOUNTS['other'];
        $date = $context['date'] ?? $expense->expense_date->toDateString();

        if ($from) {
            $this->cash->assertCanPay($from, $amount, $date);
        }

        return DB::transaction(function () use ($expense, $account, $amount, $from, $date, $context, $actor) {
            $journal = $this->poster->post([
                [
                    'account'     => $account,
                    'debit'       => $amount,
                    'gold_lot_id' => $expense->gold_lot_id,
                    'memo'        => $expense->description,
                ],
                [
                    'account' => $from ? $from->glAccount : self::PAYABLE,
                    'credit'  => $amount,
                    'memo'    => $from ? 'paid from ' . $from->label() : 'unpaid',
                ],
            ], [
                'date'        => $date,
                'memo'        => $context['memo'] ?? (ucfirst($expense->category) . ': ' . $expense->description),
                'source_type' => 'gold_expense',
                'source_id'   => $expense->id,
            ], $actor);

            $expense->forceFill([
                'journal_id'                => $journal->id,
                'paid_from_cash_account_id' => $from?->id,
            ])->save();

            return $journal;
        });
    }

    /**
     * Deals that were closed and paid out before the general ledger existed are
     * not posted by ordinary working.
     *
     * Reconstructing them means deciding where money nobody has yet explained
     * came from, which is the historical backfill's job and needs evidence, not
     * a default.
     */
    private function assertNotHistorical(GoldLot $lot): void
    {
        $alreadyDistributed = LotResult::where('gold_lot_id', $lot->id)
            ->where('status', LotResult::STATUS_DISTRIBUTED)
            ->exists();

        if ($alreadyDistributed) {
            throw new PostingRefused(
                'Deal ' . $lot->lot_code . ' was closed and its profit distributed before the general ledger '
                . 'existed. Reconstructing it belongs to the historical backfill, where the funding can be '
                . 'established from records rather than assumed.'
            );
        }
    }
}
