<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\Journal;
use App\Accounting\Security\AccountingPermission;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;

/**
 * Money arriving at, leaving, and moving between the company's own accounts.
 *
 * Every one of these is a journal and nothing else. There is no separate record
 * of "a payment" that could later disagree with the ledger, because the journal
 * line on the cash account *is* the payment.
 */
class CashService
{
    private const EPSILON = 0.00000001;

    public function __construct(private readonly JournalPoster $poster)
    {
    }

    /** Money arriving. Debit the cash account, credit wherever it came from. */
    public function receipt(CashAccount $account, float $amount, string $fromAccountCode, array $context = [], ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::CASH_POST);
        $this->assertPositive($amount);
        $this->assertActive($account);

        return $this->poster->post([
            ['account' => $account->glAccount, 'debit' => $amount, 'memo' => $context['memo'] ?? null],
            ['account' => $fromAccountCode, 'credit' => $amount, 'memo' => $context['memo'] ?? null],
        ], $this->context($context, 'Cash receipt into ' . $account->label()), $actor);
    }

    /** Money leaving. Debit whatever it was spent on, credit the cash account. */
    public function payment(CashAccount $account, float $amount, string $toAccountCode, array $context = [], ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::CASH_POST);
        $this->assertPositive($amount);
        $this->assertActive($account);
        $this->assertCanPay($account, $amount, $context["date"] ?? null);

        return $this->poster->post([
            ['account' => $toAccountCode, 'debit' => $amount, 'memo' => $context['memo'] ?? null],
            ['account' => $account->glAccount, 'credit' => $amount, 'memo' => $context['memo'] ?? null],
        ], $this->context($context, 'Cash payment from ' . $account->label()), $actor);
    }

    /**
     * Money moving between two of the company's own accounts.
     *
     * One journal with both sides, never two journals that could be half-posted.
     * The company is no richer or poorer afterwards, which is why this can never
     * touch revenue or expense.
     */
    public function transfer(CashAccount $from, CashAccount $to, float $amount, array $context = [], ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::CASH_POST);
        $this->assertPositive($amount);

        if ($from->id === $to->id) {
            throw new PostingRefused('A transfer needs two different accounts.');
        }

        $this->assertActive($from);
        $this->assertActive($to);
        $this->assertCanPay($from, $amount, $context["date"] ?? null);

        if ($from->currency_code !== $to->currency_code) {
            throw new PostingRefused(
                'Transferring between ' . $from->currency_code . ' and ' . $to->currency_code
                . ' needs an exchange rate and an FX line. Not supported yet.'
            );
        }

        $memo = $context['memo'] ?? ('Transfer ' . $from->label() . ' to ' . $to->label());

        return DB::transaction(fn () => $this->poster->post([
            ['account' => $to->glAccount, 'debit' => $amount, 'memo' => 'from ' . $from->label()],
            ['account' => $from->glAccount, 'credit' => $amount, 'memo' => 'to ' . $to->label()],
        ], $this->context($context, $memo), $actor));
    }

    private function assertPositive(float $amount): void
    {
        if ($amount <= self::EPSILON) {
            throw new PostingRefused('A cash movement must be for more than nothing.');
        }
    }

    private function assertActive(CashAccount $account): void
    {
        if (! $account->active) {
            throw new PostingRefused('Cash account ' . $account->label() . ' is not active.');
        }
    }

    /**
     * Money the company does not have cannot leave it.
     *
     * An overdraft is a real arrangement rather than an accident, so it has to
     * be permitted on the account and is limited to the agreed amount.
     */
    public function assertCanPay(CashAccount $account, float $amount, ?string $date = null): void
    {
        $balance = $account->balance($date);
        $after = round($balance - $amount, 8);
        $floor = $account->floor();

        if ($after < $floor - self::EPSILON) {
            throw new PostingRefused(
                'Paying ' . Money::format($amount) . ' from ' . $account->label()
                . ' would leave ' . Money::format($after) . ', below its floor of ' . Money::format($floor)
                . ($account->allow_negative ? ' (overdraft limit).' : ' (this account may not go negative).')
            );
        }
    }

    private function context(array $context, string $fallbackMemo): array
    {
        return array_merge([
            'source_type' => 'cash',
            'memo'        => $fallbackMemo,
        ], array_filter($context, fn ($v) => $v !== null));
    }
}
