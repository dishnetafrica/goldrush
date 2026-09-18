<?php

namespace App\Console\Commands;

use App\Investor\Exceptions\LedgerException;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\LedgerReconciler;
use App\Investor\Services\LedgerRecorder;
use App\Investor\Services\LedgerSynchroniser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The Phase 1 acceptance run: reports an investor's real position from the
 * ledger, then exercises every movement the model has to support.
 *
 * The scenarios run against the investor's own ledger inside a transaction that
 * is always rolled back, so they prove the rules on real data without creating a
 * cent of fake money.
 */
class LedgerVerifyCommand extends Command
{
    protected $signature = 'ledger:verify
                            {user : username or email}
                            {--skip-scenarios : report the position only}';

    protected $description = 'Full Phase 1 verification of an investor ledger';

    private const TOLERANCE = 0.005;

    public function handle(
        LedgerReconciler $reconciler,
        LedgerRecorder $recorder,
        LedgerSynchroniser $synchroniser,
    ): int {
        $user = User::where('username', $this->argument('user'))
            ->orWhere('email', $this->argument('user'))
            ->first();

        if (! $user) {
            $this->error('No user matches ' . $this->argument('user'));

            return self::FAILURE;
        }

        $this->section('PHASE 1 VERIFICATION — ' . $user->username . ' (' . $user->email . ')');

        $report = $reconciler->forUser($user);
        $entries = LedgerEntry::forUser($user->id)->chronological()->get();

        $this->reportLedger($user, $entries, $report);

        $ok = $report['passed'];

        if (! $this->option('skip-scenarios')) {
            $ok = $this->runScenarios($user, $recorder, $synchroniser) && $ok;
        }

        $this->section('OVERALL');
        if ($ok) {
            $this->info('PASS — the ledger reconciles and every movement rule holds.');
        } else {
            $this->error('FAIL — see the discrepancies above. Do not generate investor documents.');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function reportLedger(User $user, $entries, array $report): void
    {
        $transactionCount = DB::table('transactions')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('receiver_id', $user->id);
            })->count();

        $movements = $entries->groupBy('group_uuid')->count();
        $corrections = $entries->where('event_type', LedgerEvent::BUCKET_CORRECTION);

        $this->section('1-3  SOURCES');
        $this->line('  Transactions discovered      : ' . $transactionCount);
        $this->line('  Ledger movements recorded    : ' . $movements);
        $this->line('  Ledger entries (legs)        : ' . $entries->count());
        $this->line('  Historical bucket corrections: ' . $corrections->groupBy('group_uuid')->count()
            . ($corrections->isNotEmpty()
                ? '  (' . number_format(abs($corrections->where('bucket', Bucket::PROFIT)->sum('amount_usd')), 2) . ' USD reclassified)'
                : ''));

        $this->section('LEDGER ENTRIES');
        $this->table(
            ['Seq', 'Date', 'Event', 'Bucket', 'Amount', 'Avail', 'Profit', 'Committed', 'Reference', 'Original trx'],
            $entries->map(fn ($e) => [
                $e->seq,
                $e->occurred_at->format('d M y'),
                $e->event_type,
                $e->bucket,
                number_format($e->amount_usd, 2),
                number_format($e->balance_available, 2),
                number_format($e->balance_profit, 2),
                number_format($e->balance_committed, 2),
                $e->reference,
                $e->trx_id ?? '-',
            ])->all()
        );

        // This report covers the whole of an investor's history, which by
        // definition starts from nothing. Phase 2's period statements will take
        // their opening position from the last entry before the period instead.
        $opening = Bucket::zeroed();

        $position = $report['position'];
        $totals = $report['totals'];

        $this->section('4-11  POSITION');
        $this->line('  4.  Opening position         : ' . number_format(array_sum($opening), 2)
            . '   (available ' . number_format($opening[Bucket::AVAILABLE], 2)
            . ', profit ' . number_format($opening[Bucket::PROFIT], 2)
            . ', committed ' . number_format($opening[Bucket::COMMITTED], 2) . ')');
        $this->line('  5.  Total external money in  : ' . number_format($totals['external_in'], 2));
        $this->line('  6.  Total external money out : ' . number_format($totals['external_out'], 2));
        $this->line('  7.  Internal movement (gross): ' . number_format($totals['internal_gross'], 2)
            . '   (net effect on total position: 0.00)');
        $this->line('  8.  Available balance        : ' . number_format($position[Bucket::AVAILABLE], 2));
        $this->line('  9.  Profit balance           : ' . number_format($position[Bucket::PROFIT], 2));
        $this->line('  10. Capital committed        : ' . number_format($position[Bucket::COMMITTED], 2));
        $this->line('  11. TOTAL INVESTOR POSITION  : ' . number_format($report['total'], 2));

        $identity = round(
            array_sum($opening) + $totals['external_in'] - $totals['external_out'] - $report['total'],
            8
        );

        $this->newLine();
        $this->line('  Opening + in - out = closing :  '
            . number_format(array_sum($opening), 2) . ' + ' . number_format($totals['external_in'], 2)
            . ' - ' . number_format($totals['external_out'], 2) . ' = ' . number_format($report['total'], 2)
            . (abs($identity) < self::TOLERANCE ? '   [PASS]' : '   [FAIL by ' . number_format($identity, 2) . ']'));

        $this->section('12-16  RECONCILIATION');
        foreach ([
            '12. against transactions'            => $report['checks']['transactions'],
            '13. against user_wallets'            => $report['checks']['wallet'],
            '14. against gold_capital_allocations' => $report['checks']['allocations'],
            '    internal consistency'            => $report['checks']['internal'],
        ] as $label => $check) {
            $this->line(sprintf('  [%s] %-38s %s', $check['passed'] ? 'PASS' : 'FAIL', $label, $check['message']));
        }

        $this->newLine();
        $this->line('  15. Overall                  : ' . ($report['passed'] ? 'PASS' : 'FAIL'));
        $this->line('  16. Discrepancies            : ' . (count($report['discrepancies']) ?: 'none'));

        foreach ($report['discrepancies'] as $d) {
            $this->line('      - ' . $d);
        }
    }

    /**
     * Exercises every movement the three-bucket model has to support.
     *
     * Everything happens inside a transaction that is rolled back at the end, so
     * the investor's real position is untouched when this returns.
     */
    private function runScenarios(User $user, LedgerRecorder $recorder, LedgerSynchroniser $synchroniser): bool
    {
        $this->section('SCENARIOS A-L  (run on real data, always rolled back)');

        $results = [];
        DB::beginTransaction();

        try {
            $before = $synchroniser->currentPosition($user->id);
            $startTotal = round(array_sum($before), 8);

            $results[] = $this->scenario('A  Deposit', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::DEPOSIT,
                [['bucket' => Bucket::AVAILABLE, 'amount' => 500.00]],
                ['description' => 'Scenario deposit'],
            ), expectTotalChange: 500.00);

            $results[] = $this->scenario('B  Allocate from Available', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::CAPITAL_ALLOCATED,
                [
                    ['bucket' => Bucket::AVAILABLE, 'amount' => -300.00],
                    ['bucket' => Bucket::COMMITTED, 'amount' => 300.00],
                ],
                ['description' => 'Scenario allocation from available'],
            ), expectTotalChange: 0.0);

            $results[] = $this->scenario('C  Allocate from Profit', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::CAPITAL_ALLOCATED,
                [
                    ['bucket' => Bucket::PROFIT,    'amount' => -100.00],
                    ['bucket' => Bucket::COMMITTED, 'amount' => 100.00],
                ],
                ['description' => 'Scenario allocation from profit'],
            ), expectTotalChange: 0.0);

            $results[] = $this->scenario('D  Allocate from both', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::CAPITAL_ALLOCATED,
                [
                    ['bucket' => Bucket::AVAILABLE, 'amount' => -60.00],
                    ['bucket' => Bucket::PROFIT,    'amount' => -40.00],
                    ['bucket' => Bucket::COMMITTED, 'amount' => 100.00],
                ],
                ['description' => 'Scenario allocation from both'],
            ), expectTotalChange: 0.0);

            // The one that used to be wrong: the 40 taken from profit must go back
            // to profit, not swell the available balance.
            $profitBeforeReturn = $synchroniser->currentPosition($user->id)[Bucket::PROFIT];

            $results[] = $this->scenario('E  Return preserves bucket', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::CAPITAL_RETURNED,
                [
                    ['bucket' => Bucket::COMMITTED, 'amount' => -100.00],
                    ['bucket' => Bucket::AVAILABLE, 'amount' => 60.00],
                    ['bucket' => Bucket::PROFIT,    'amount' => 40.00],
                ],
                ['description' => 'Scenario capital return'],
            ), expectTotalChange: 0.0);

            $profitAfterReturn = $synchroniser->currentPosition($user->id)[Bucket::PROFIT];
            $results[] = [
                'name'   => 'E2 Profit restored to profit bucket',
                'passed' => abs(($profitAfterReturn - $profitBeforeReturn) - 40.00) < self::TOLERANCE,
                'detail' => 'profit moved by ' . number_format($profitAfterReturn - $profitBeforeReturn, 2) . ', expected 40.00',
            ];

            $results[] = $this->scenario('F  Profit credit', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::PROFIT_CREDITED,
                [['bucket' => Bucket::PROFIT, 'amount' => 250.00]],
                ['description' => 'Scenario profit'],
            ), expectTotalChange: 250.00);

            $results[] = $this->scenario('G  Withdrawal request', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::WITHDRAWAL_REQUESTED,
                [['bucket' => Bucket::PROFIT, 'amount' => -150.00]],
                ['description' => 'Scenario withdrawal'],
            ), expectTotalChange: -150.00);

            $results[] = $this->scenario('H  Withdrawal reversed', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::WITHDRAWAL_REVERSED,
                [['bucket' => Bucket::PROFIT, 'amount' => 150.00]],
                ['description' => 'Scenario withdrawal rejected'],
            ), expectTotalChange: 150.00);

            $results[] = $this->scenario('I  Reinvestment', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::CAPITAL_ALLOCATED,
                [
                    ['bucket' => Bucket::PROFIT,    'amount' => -250.00],
                    ['bucket' => Bucket::COMMITTED, 'amount' => 250.00],
                ],
                ['description' => 'Scenario reinvestment of earned profit'],
            ), expectTotalChange: 0.0);

            $results[] = $this->duplicateProtection($user, $synchroniser);

            $results[] = $this->scenario('K  Correction', $user, $synchroniser, fn () => $recorder->post(
                $user->id,
                LedgerEvent::BUCKET_CORRECTION,
                [
                    ['bucket' => Bucket::AVAILABLE, 'amount' => -25.00],
                    ['bucket' => Bucket::PROFIT,    'amount' => 25.00],
                ],
                ['description' => 'Scenario correction'],
            ), expectTotalChange: 0.0);

            $results[] = $this->refusesUnbalanced($user, $recorder);
            $results[] = $this->refusesImmutability($user);

            $endTotal = round(array_sum($synchroniser->currentPosition($user->id)), 8);
            $expected = round($startTotal + 500.00 + 250.00, 8);

            $results[] = [
                'name'   => 'Closing total matches every external movement',
                'passed' => abs($endTotal - $expected) < self::TOLERANCE,
                'detail' => 'expected ' . number_format($expected, 2) . ', got ' . number_format($endTotal, 2),
            ];
        } finally {
            DB::rollBack();
        }

        $this->table(
            ['Scenario', 'Result', 'Detail'],
            array_map(fn ($r) => [$r['name'], $r['passed'] ? 'PASS' : 'FAIL', $r['detail']], $results)
        );

        $failed = array_filter($results, fn ($r) => ! $r['passed']);

        $this->line('  ' . (count($results) - count($failed)) . ' of ' . count($results) . ' scenarios passed.');
        $this->line('  Investor position after rollback is unchanged; no money was created.');

        return $failed === [];
    }

    /** Runs one movement and checks what it did to the total position. */
    private function scenario(
        string $name,
        User $user,
        LedgerSynchroniser $synchroniser,
        callable $movement,
        float $expectTotalChange,
    ): array {
        $before = $synchroniser->currentPosition($user->id);

        try {
            $movement();
        } catch (\Throwable $e) {
            return ['name' => $name, 'passed' => false, 'detail' => $e->getMessage()];
        }

        $after = $synchroniser->currentPosition($user->id);
        $change = round(array_sum($after) - array_sum($before), 8);
        $sumsCorrectly = abs($change - $expectTotalChange) < self::TOLERANCE;

        return [
            'name'   => $name,
            'passed' => $sumsCorrectly,
            'detail' => 'A+P+C = ' . number_format(array_sum($after), 2)
                . ' (moved ' . number_format($change, 2) . ', expected ' . number_format($expectTotalChange, 2) . ')',
        ];
    }

    private function duplicateProtection(User $user, LedgerSynchroniser $synchroniser): array
    {
        $before = LedgerEntry::forUser($user->id)->count();

        try {
            $synchroniser->syncUser($user);
            $synchroniser->syncUser($user);
        } catch (\Throwable $e) {
            return ['name' => 'J  Duplicate protection', 'passed' => false, 'detail' => $e->getMessage()];
        }

        $after = LedgerEntry::forUser($user->id)->count();

        return [
            'name'   => 'J  Duplicate protection',
            'passed' => $before === $after,
            'detail' => 'two further syncs added ' . ($after - $before) . ' entries',
        ];
    }

    private function refusesUnbalanced(User $user, LedgerRecorder $recorder): array
    {
        try {
            $recorder->post($user->id, LedgerEvent::CAPITAL_ALLOCATED, [
                ['bucket' => Bucket::AVAILABLE, 'amount' => -100.00],
                ['bucket' => Bucket::COMMITTED, 'amount' => 90.00],
            ]);
        } catch (LedgerException $e) {
            return [
                'name'   => 'L  Unbalanced internal move refused',
                'passed' => true,
                'detail' => 'refused: total position cannot change on a transfer',
            ];
        }

        return [
            'name'   => 'L  Unbalanced internal move refused',
            'passed' => false,
            'detail' => 'ACCEPTED an internal movement that changed the total position',
        ];
    }

    private function refusesImmutability(User $user): array
    {
        $entry = LedgerEntry::forUser($user->id)->orderByDesc('seq')->first();

        if (! $entry) {
            return ['name' => 'L2 Entries are append only', 'passed' => false, 'detail' => 'no entry to test'];
        }

        try {
            $entry->update(['amount_usd' => 1.00]);
        } catch (LedgerException $e) {
            return ['name' => 'L2 Entries are append only', 'passed' => true, 'detail' => 'update refused'];
        }

        return ['name' => 'L2 Entries are append only', 'passed' => false, 'detail' => 'an entry was rewritten'];
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line('<options=bold>' . $title . '</>');
        $this->line(str_repeat('-', max(40, strlen($title))));
    }
}
