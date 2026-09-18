<?php

namespace App\Console\Commands;

use App\Accounting\Models\CashAccount;
use App\Accounting\Services\ExpenseEvidenceStore;
use App\Accounting\Services\ExpenseWorkflow;
use App\GoldTrading\Models\TradingExpense;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * Walks an expense through its life: submitted, checked, agreed, posted, paid.
 *
 * Each step is a separate act by a named person, which is the whole point. A
 * command that did all of it at once would be a way of recording that money
 * was spent, not a way of controlling whether it should have been.
 */
class ExpenseCommand extends Command
{
    protected $signature = 'expense
                            {action : list|show|submit|approve|reject|post|pay|reverse|reclassify|evidence}
                            {ref? : the expense reference, e.g. EXP-20260917-000001}
                            {--from= : cash or bank account code the money came from}
                            {--date= : posting or payment date}
                            {--memo= }
                            {--reason= : why it was rejected, reversed or reclassified}
                            {--file= : evidence file to attach}
                            {--to-category= : reclassify into this category}
                            {--capitalise : reclassify into inventory}
                            {--expense : reclassify out of inventory and into expenses}
                            {--status= : filter the list}
                            {--lot= : filter the list to one deal}';

    protected $description = 'Submit, approve, post, pay or correct a company expense';

    public function handle(ExpenseWorkflow $workflow, ExpenseEvidenceStore $evidence): int
    {
        $action = strtolower((string) $this->argument('action'));

        if ($action === 'list') {
            return $this->listExpenses();
        }

        $expense = $this->find();

        if (! $expense) {
            return self::FAILURE;
        }

        try {
            match ($action) {
                'show'       => $this->show($expense),
                'submit'     => $this->announce($workflow->submit($expense), 'submitted for approval'),
                'approve'    => $this->announce($workflow->approve($expense, null, $this->option('reason')), 'approved'),
                'reject'     => $this->announce(
                    $workflow->reject($expense, (string) $this->option('reason')),
                    'rejected'
                ),
                'post'       => $this->posted($workflow, $expense),
                'pay'        => $this->paid($workflow, $expense),
                'reverse'    => $this->reversed($workflow, $expense),
                'reclassify' => $this->reclassified($workflow, $expense),
                'evidence'   => $this->attach($evidence, $expense),
                default      => throw new \InvalidArgumentException(
                    'Action must be list, show, submit, approve, reject, post, pay, reverse, reclassify or evidence.'
                ),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function find(): ?TradingExpense
    {
        $ref = $this->argument('ref');

        if (! $ref) {
            $this->error('Name the expense: expense ' . $this->argument('action') . ' EXP-20260917-000001');

            return null;
        }

        $expense = TradingExpense::with(['lot', 'journal.lines.account'])
            ->where('reference', $ref)
            ->orWhere('id', is_numeric($ref) ? (int) $ref : 0)
            ->first();

        if (! $expense) {
            $this->error('No expense found matching ' . $ref . '.');

            return null;
        }

        return $expense;
    }

    private function listExpenses(): int
    {
        $expenses = TradingExpense::query()
            ->when($this->option('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($this->option('lot'), fn ($q, $lot) => $q->whereHas('lot', fn ($l) => $l->where('lot_code', $lot)))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        if ($expenses->isEmpty()) {
            $this->warn('No expenses match.');

            return self::SUCCESS;
        }

        $this->table(
            ['Reference', 'Date', 'Category', 'Description', 'USD', 'Status', 'Payment', 'Treatment'],
            $expenses->map(fn (TradingExpense $e) => [
                $e->reference ?? '#' . $e->id,
                $e->expense_date->format('d M Y'),
                $e->category,
                \Illuminate\Support\Str::limit($e->description, 32),
                Money::format((float) $e->amount_usd),
                $e->status,
                $e->payment_status,
                $e->capitalised ? 'inventory' : 'expense',
            ])->all()
        );

        return self::SUCCESS;
    }

    private function show(TradingExpense $expense): void
    {
        $rows = [
            ['Reference', $expense->reference ?? '#' . $expense->id],
            ['Status', $expense->status . ' / ' . $expense->payment_status],
            ['Date', $expense->expense_date->format('d M Y')],
            ['Category', $expense->category],
            ['Description', $expense->description],
            ['Payee', $expense->payee_name ?? '-'],
            ['Invoice', $expense->external_ref ?? '-'],
            ['Amount', Money::format((float) $expense->amount_local) . ' ' . $expense->currency_code
                . ' at ' . $expense->fx_rate_to_usd . ' = ' . Money::format((float) $expense->amount_usd) . ' USD'],
            ['Deal', $expense->lot?->lot_code ?? '-'],
            ['Treatment', $expense->capitalised
                ? 'capitalised into the gold' . ($expense->capitalised_into ? ' (' . $expense->capitalised_into . ')' : '')
                : 'expense of the period'],
            ['Evidence', $expense->hasEvidence()
                ? $expense->evidence_filename . ' (' . number_format((float) $expense->evidence_bytes / 1024, 1)
                    . ' KB, ' . ($expense->evidenceIntact() ? 'intact' : 'ALTERED OR MISSING') . ')'
                : 'none attached'],
            ['Submitted', $this->actorLine($expense->submittedBy, $expense->submitted_at)],
            ['Approved', $this->actorLine($expense->approvedBy, $expense->approved_at)],
            ['Rejected', $expense->rejected_at
                ? $expense->rejected_at->format('d M Y H:i') . ' - ' . $expense->rejection_reason
                : '-'],
            ['Journal', $expense->journal?->reference ?? 'not posted'],
            ['Payment journal', $expense->paymentJournal?->reference ?? 'not paid'],
        ];

        if ($expense->sod_exception) {
            $rows[] = ['Control waived', $expense->sod_exception_reason];
        }

        $this->table(['Field', 'Value'], $rows);

        if ($expense->journal) {
            $this->newLine();
            $this->line('As posted:');
            $this->table(
                ['Account', 'Debit', 'Credit', 'Memo'],
                $expense->journal->lines->map(fn ($l) => [
                    $l->account->label(),
                    $l->debit > 0 ? Money::format((float) $l->debit) : '',
                    $l->credit > 0 ? Money::format((float) $l->credit) : '',
                    $l->memo,
                ])->all()
            );
        }
    }

    private function posted(ExpenseWorkflow $workflow, TradingExpense $expense): void
    {
        $already = $expense->journal_id !== null;

        $journal = $workflow->post($expense, $this->cashAccount(), array_filter([
            'date' => $this->option('date'),
            'memo' => $this->option('memo'),
        ]));

        if ($already) {
            $this->warn('Already posted as ' . $journal->reference . '; nothing was posted a second time.');
        } else {
            $this->info('Posted ' . $journal->reference . ' - ' . $journal->memo);
        }

        $this->table(
            ['Account', 'Debit', 'Credit', 'Memo'],
            $journal->lines->map(fn ($l) => [
                $l->account->label(),
                $l->debit > 0 ? Money::format((float) $l->debit) : '',
                $l->credit > 0 ? Money::format((float) $l->credit) : '',
                $l->memo,
            ])->all()
        );

        if ($expense->fresh()->payment_status === TradingExpense::PAYMENT_UNPAID) {
            $this->line('The company now owes this. Pay it with: expense pay ' . $expense->reference . ' --from=CODE');
        }
    }

    private function paid(ExpenseWorkflow $workflow, TradingExpense $expense): void
    {
        $account = $this->cashAccount();

        if (! $account) {
            throw new \InvalidArgumentException('Name the account the money came from: --from=CODE');
        }

        $already = $expense->payment_journal_id !== null;
        $journal = $workflow->pay($expense, $account, array_filter([
            'date' => $this->option('date'),
            'memo' => $this->option('memo'),
        ]));

        $this->info(($already ? 'Already paid by ' : 'Paid by ') . $journal->reference
            . ' from ' . $account->label() . '; balance now ' . Money::format($account->fresh()->balance()));
    }

    private function reversed(ExpenseWorkflow $workflow, TradingExpense $expense): void
    {
        $journal = $workflow->reverse($expense, (string) $this->option('reason'));

        $this->info('Reversed by ' . $journal->reference . '. Both entries stay in the books.');
    }

    private function reclassified(ExpenseWorkflow $workflow, TradingExpense $expense): void
    {
        $to = array_filter([
            'category' => $this->option('to-category'),
            'date'     => $this->option('date'),
        ]);

        if ($this->option('capitalise')) {
            $to['capitalised'] = true;
        }

        if ($this->option('expense')) {
            $to['capitalised'] = false;
        }

        $journal = $workflow->reclassify($expense, $to, (string) $this->option('reason'));

        $fresh = $expense->fresh();

        $this->info('Reclassified by ' . $journal->reference . ': now ' . $fresh->category
            . ($fresh->capitalised ? ', capitalised into ' . $fresh->capitalised_into : ', an expense of the period'));
        $this->line('Cash was not touched; only where the cost sits has changed.');
    }

    private function attach(ExpenseEvidenceStore $store, TradingExpense $expense): void
    {
        $file = (string) $this->option('file');

        if ($file === '' || ! is_readable($file)) {
            throw new \InvalidArgumentException('Give a readable file: --file=/path/to/receipt.pdf');
        }

        $store->attach($expense, (string) file_get_contents($file), basename($file));

        $fresh = $expense->fresh();

        $this->info('Filed ' . $fresh->evidence_filename . ' ('
            . number_format((float) $fresh->evidence_bytes / 1024, 1) . ' KB) against ' . $fresh->reference);
        $this->line('Stored privately under ' . $store->root() . '; it is not served to the web.');
    }

    private function announce(TradingExpense $expense, string $what): void
    {
        $this->info($expense->reference . ' ' . $what . '.');

        if ($expense->sod_exception) {
            $this->warn('A control was waived: ' . $expense->sod_exception_reason);
        }

        if ($expense->status === TradingExpense::STATUS_APPROVED) {
            $this->line('Next: expense post ' . $expense->reference . ' [--from=CODE]');
        }
    }

    private function actorLine($actor, $at): string
    {
        if ($at === null) {
            return '-';
        }

        return $at->format('d M Y H:i') . ($actor ? ' by ' . ($actor->username ?? $actor->email) : ' (console)');
    }

    private function cashAccount(): ?CashAccount
    {
        if (! $this->option('from')) {
            return null;
        }

        return CashAccount::where('code', $this->option('from'))->firstOrFail();
    }
}
