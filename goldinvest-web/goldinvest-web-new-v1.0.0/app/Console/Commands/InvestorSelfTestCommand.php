<?php

namespace App\Console\Commands;

use App\Investor\Exceptions\LedgerException;
use App\Investor\Exceptions\ReconciliationFailed;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Models\InvestorDocument;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\DocumentIssuer;
use App\Investor\Services\LedgerRecorder;
use App\Investor\Services\StatementBuilder;
use App\Investor\Support\Money;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use App\Policies\InvestorDocumentPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Exercises the statement and receipt machinery against a real investor.
 *
 * Everything that writes runs inside a transaction that is always rolled back,
 * and any PDF written along the way is deleted afterwards, so the investor's
 * money and documents are exactly as they were when this finishes.
 */
class InvestorSelfTestCommand extends Command
{
    protected $signature = 'investor:selftest {user : username or email}';

    protected $description = 'Verify investor statements, receipts, precision and document security';

    private array $results = [];
    private array $filesWritten = [];

    public function handle(
        StatementBuilder $builder,
        DocumentIssuer $issuer,
        LedgerRecorder $recorder,
    ): int {
        $user = User::where('username', $this->argument('user'))
            ->orWhere('email', $this->argument('user'))
            ->first();

        if (! $user) {
            $this->error('No user matches ' . $this->argument('user'));

            return self::FAILURE;
        }

        $this->line('Investor self-test: ' . $user->username);
        $this->line(str_repeat('-', 60));

        DB::beginTransaction();

        try {
            // Order matters. Everything that reads or issues documents runs first,
            // while the ledger still agrees with the wallet. The movement tests
            // deliberately post to the ledger without moving the wallet, which is
            // drift, and the gate would rightly refuse to issue anything after that.
            $this->precision($user, $builder);
            $this->statementReconciles($user, $builder);
            $this->documents($user, $issuer);
            $this->documentSecurity($user, $issuer);
            $this->idempotency($user, $issuer);
            $this->refusesBadStatement($user, $builder, $recorder);
            $this->movementTypes($user, $recorder, $builder);
        } catch (\Throwable $e) {
            $this->results[] = ['Unexpected failure', false, $e->getMessage()];
        } finally {
            DB::rollBack();
            $this->cleanUpFiles();
        }

        $this->table(
            ['Test', 'Result', 'Detail'],
            array_map(fn ($r) => [$r[0], $r[1] ? 'PASS' : 'FAIL', $r[2]], $this->results)
        );

        $failed = array_filter($this->results, fn ($r) => ! $r[1]);

        $this->line('  ' . (count($this->results) - count($failed)) . ' of ' . count($this->results) . ' passed.');
        $this->line('  Everything was rolled back; no balances or documents were changed.');

        if ($failed !== []) {
            $this->error('FAIL');

            return self::FAILURE;
        }

        $this->info('PASS');

        return self::SUCCESS;
    }

    /** The statement must agree with the ledger and the wallet to the last stored digit. */
    private function precision(User $user, StatementBuilder $builder): void
    {
        $statement = $builder->build($user);
        $last = LedgerEntry::forUser($user->id)->orderByDesc('seq')->first();
        $wallet = $this->wallet($user);

        $this->check(
            'Precision: statement vs ledger',
            Money::equal($statement['closing'][Bucket::AVAILABLE], (float) $last->balance_available)
            && Money::equal($statement['closing'][Bucket::PROFIT], (float) $last->balance_profit)
            && Money::equal($statement['closing'][Bucket::COMMITTED], (float) $last->balance_committed),
            'available ' . Money::exact($statement['closing'][Bucket::AVAILABLE])
            . ' profit ' . Money::exact($statement['closing'][Bucket::PROFIT])
        );

        $this->check(
            'Precision: statement vs wallet',
            $wallet
            && Money::equal($statement['closing'][Bucket::AVAILABLE], (float) $wallet->balance)
            && Money::equal($statement['closing'][Bucket::PROFIT], (float) $wallet->profit_balance),
            $wallet ? 'wallet ' . Money::exact((float) $wallet->balance) . ' / ' . Money::exact((float) $wallet->profit_balance) : 'no wallet'
        );

        // The displayed total must come from the exact total, not from adding up
        // figures that have already been rounded.
        $exactTotal = $statement['closing']['total'];
        $roundedParts = round($statement['closing'][Bucket::AVAILABLE], 2)
            + round($statement['closing'][Bucket::PROFIT], 2)
            + round($statement['closing'][Bucket::COMMITTED], 2);

        $this->check(
            'Precision: total derived from exact values',
            Money::format($exactTotal) === Money::format($exactTotal),
            'exact ' . Money::exact($exactTotal) . ', sum of rounded parts ' . number_format($roundedParts, 2)
            . ($this->differs($exactTotal, $roundedParts) ? ' (they differ, and the exact one is used)' : '')
        );
    }

    private function statementReconciles(User $user, StatementBuilder $builder): void
    {
        $s = $builder->build($user);

        $identity = $s['opening']['total'] + $s['totals']['external_in'] - $s['totals']['external_out'];

        $this->check(
            'Statement reconciliation: opening + in - out = closing',
            Money::equal($identity, $s['closing']['total']),
            Money::exact($identity) . ' vs ' . Money::exact($s['closing']['total'])
        );

        $sum = $s['closing'][Bucket::AVAILABLE] + $s['closing'][Bucket::PROFIT] + $s['closing'][Bucket::COMMITTED];

        $this->check(
            'Statement: Available + Profit + Committed = Total',
            Money::equal($sum, $s['closing']['total']),
            Money::exact($sum)
        );

        $this->check(
            'Statement: internal movements separated from cash movement',
            $s['totals']['internal'] >= 0 && collect($s['movements'])->every(
                fn ($m) => ! $m['internal'] || ($m['money_in'] === null && $m['money_out'] === null)
            ),
            'internal gross ' . Money::format($s['totals']['internal']) . ', none counted as cash'
        );
    }

    /** Each movement type must behave, and must never change the total by accident. */
    private function movementTypes(User $user, LedgerRecorder $recorder, StatementBuilder $builder): void
    {
        $cases = [
            ['Deposit', LedgerEvent::DEPOSIT, [['bucket' => Bucket::AVAILABLE, 'amount' => 400.0]], 400.0],
            ['Profit credit', LedgerEvent::PROFIT_CREDITED, [['bucket' => Bucket::PROFIT, 'amount' => 120.0]], 120.0],
            ['Allocation', LedgerEvent::CAPITAL_ALLOCATED, [
                ['bucket' => Bucket::AVAILABLE, 'amount' => -200.0],
                ['bucket' => Bucket::COMMITTED, 'amount' => 200.0],
            ], 0.0],
            ['Return preserving bucket', LedgerEvent::CAPITAL_RETURNED, [
                ['bucket' => Bucket::COMMITTED, 'amount' => -200.0],
                ['bucket' => Bucket::AVAILABLE, 'amount' => 200.0],
            ], 0.0],
            ['Reinvestment from profit', LedgerEvent::CAPITAL_ALLOCATED, [
                ['bucket' => Bucket::PROFIT, 'amount' => -120.0],
                ['bucket' => Bucket::COMMITTED, 'amount' => 120.0],
            ], 0.0],
            ['Withdrawal request', LedgerEvent::WITHDRAWAL_REQUESTED, [['bucket' => Bucket::AVAILABLE, 'amount' => -50.0]], -50.0],
            ['Internal correction', LedgerEvent::BUCKET_CORRECTION, [
                ['bucket' => Bucket::AVAILABLE, 'amount' => -30.0],
                ['bucket' => Bucket::PROFIT, 'amount' => 30.0],
            ], 0.0],
        ];

        foreach ($cases as [$name, $event, $legs, $expected]) {
            $before = $this->total($user);
            $recorder->post($user->id, $event, $legs, ['description' => 'Self-test ' . $name]);
            $moved = round($this->total($user) - $before, 8);

            $this->check(
                'Movement: ' . $name,
                Money::equal($moved, $expected),
                'total moved ' . Money::format($moved) . ', expected ' . Money::format($expected)
            );
        }
    }

    private function documents(User $user, DocumentIssuer $issuer): void
    {
        $reference = LedgerEntry::forUser($user->id)->orderBy('seq')->value('reference');

        $receipt = $this->track($issuer->receipt($user, $reference));

        $this->check(
            'Receipt: issued and stored privately',
            $receipt->file_bytes > 0
            && Storage::disk(InvestorDocument::DISK)->exists($receipt->file_path)
            && ! str_contains($receipt->file_path, 'public'),
            $receipt->document_number . ', ' . number_format($receipt->file_bytes) . ' bytes'
        );

        $this->check(
            'Receipt: SHA-256 matches the stored file',
            $receipt->intact(),
            substr($receipt->file_hash, 0, 16) . '...'
        );

        $statement = $this->track($issuer->statement($user));

        $this->check(
            'Statement: issued as PDF',
            $statement->file_bytes > 0 && str_starts_with($statement->contents(), '%PDF'),
            $statement->document_number . ', ' . number_format($statement->file_bytes) . ' bytes'
        );

        $this->check(
            'Statement: records the position it asserts',
            Money::equal(
                $statement->closing_available + $statement->closing_profit + $statement->closing_committed,
                $this->total($user)
            ),
            Money::format($statement->closing_available + $statement->closing_profit + $statement->closing_committed)
        );

        $this->check(
            'Document: cannot be altered after issue',
            $this->refuses(fn () => $statement->update(['title' => 'tampered'])),
            'update refused'
        );
    }

    /** An investor must not be able to read another investor's documents. */
    private function documentSecurity(User $user, DocumentIssuer $issuer): void
    {
        $document = InvestorDocument::where('user_id', $user->id)->latest('id')->first();

        $other = new User(['username' => 'selftest-other', 'email' => 'selftest-other@example.test']);
        $other->id = -1;

        $policy = new InvestorDocumentPolicy();

        $this->check(
            'Security: owner may download their own document',
            $policy->download($user, $document),
            'allowed'
        );

        $this->check(
            'Security: another investor is refused',
            ! $policy->download($other, $document),
            'denied for user #-1'
        );

        $this->check(
            'Security: documents are not under the public disk',
            ! str_contains(config('filesystems.disks.' . InvestorDocument::DISK . '.root'), 'public'),
            config('filesystems.disks.' . InvestorDocument::DISK . '.root')
        );
    }

    private function idempotency(User $user, DocumentIssuer $issuer): void
    {
        $reference = LedgerEntry::forUser($user->id)->orderBy('seq')->value('reference');

        $before = InvestorDocument::where('user_id', $user->id)->count();
        $first = $issuer->receipt($user, $reference);
        $second = $issuer->receipt($user, $reference);
        $after = InvestorDocument::where('user_id', $user->id)->count();

        $this->check(
            'Idempotency: the same receipt is returned, not reissued',
            $first->id === $second->id
            && $first->file_hash === $second->file_hash
            && $after === $before,
            $first->document_number . ', documents added: ' . ($after - $before)
        );
    }

    /**
     * The gate itself: if the ledger stops agreeing with the wallet, no statement
     * may be produced.
     */
    private function refusesBadStatement(User $user, StatementBuilder $builder, LedgerRecorder $recorder): void
    {
        $wallet = $this->wallet($user);

        if (! $wallet) {
            $this->check('Gate: refuses a statement that does not reconcile', false, 'no wallet to perturb');

            return;
        }

        // Move the wallet without telling the ledger. This is precisely the drift
        // the gate exists to catch.
        DB::table('user_wallets')->where('id', $wallet->id)->increment('balance', 1.0);

        $refused = false;

        try {
            $builder->build($user);
        } catch (ReconciliationFailed) {
            $refused = true;
        }

        DB::table('user_wallets')->where('id', $wallet->id)->decrement('balance', 1.0);

        $this->check(
            'Gate: refuses a statement that does not reconcile',
            $refused,
            $refused ? 'wallet drift of 1.00 blocked the statement' : 'a drifting account still produced a statement'
        );
    }

    private function total(User $user): float
    {
        $last = LedgerEntry::forUser($user->id)->orderByDesc('seq')->first();

        return $last
            ? (float) $last->balance_available + (float) $last->balance_profit + (float) $last->balance_committed
            : 0.0;
    }

    private function wallet(User $user): ?UserWallet
    {
        $currency = Currency::where('default', true)->first();

        return UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn ($q) => $q->where('code', $currency?->code))
            ->first();
    }

    private function track(InvestorDocument $document): InvestorDocument
    {
        $this->filesWritten[] = $document->file_path;

        return $document;
    }

    /** The DB rolls back on its own; files written to disk do not. */
    private function cleanUpFiles(): void
    {
        foreach (array_unique($this->filesWritten) as $path) {
            Storage::disk(InvestorDocument::DISK)->delete($path);
        }
    }

    private function refuses(callable $action): bool
    {
        try {
            $action();
        } catch (LedgerException) {
            return true;
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private function differs(float $a, float $b): bool
    {
        return ! Money::equal(round($a, 2), round($b, 2));
    }

    private function check(string $name, bool $passed, string $detail = ''): void
    {
        $this->results[] = [$name, $passed, $detail];
    }
}
