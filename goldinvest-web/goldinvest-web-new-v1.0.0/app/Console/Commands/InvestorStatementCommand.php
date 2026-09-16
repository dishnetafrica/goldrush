<?php

namespace App\Console\Commands;

use App\Investor\Exceptions\ReconciliationFailed;
use App\Investor\Services\DocumentIssuer;
use App\Investor\Support\Money;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Issues an investor account statement as a PDF.
 *
 * Refuses outright if the statement does not reconcile against the ledger, so
 * a failure here means something is wrong with the accounts, not with the PDF.
 */
class InvestorStatementCommand extends Command
{
    protected $signature = 'investor:statement
                            {user : username or email}
                            {--from= : period start, YYYY-MM-DD}
                            {--to= : period end, YYYY-MM-DD}';

    protected $description = 'Generate an investor account statement PDF';

    public function handle(DocumentIssuer $issuer): int
    {
        $user = User::where('username', $this->argument('user'))
            ->orWhere('email', $this->argument('user'))
            ->first();

        if (! $user) {
            $this->error('No user matches ' . $this->argument('user'));

            return self::FAILURE;
        }

        try {
            $document = $issuer->statement($user, $this->option('from'), $this->option('to'));
        } catch (ReconciliationFailed $e) {
            $this->error('No statement was produced: this account does not reconcile.');
            foreach ($e->failures as $failure) {
                $this->line('  - ' . $failure);
            }

            return self::FAILURE;
        }

        $this->info('Issued ' . $document->document_number);
        $this->table(['Field', 'Value'], [
            ['Investor', $user->username],
            ['Period', trim(($document->period_start?->format('d M Y') ?? 'start') . ' to ' . ($document->period_end?->format('d M Y') ?? 'now'))],
            ['Available', Money::format($document->closing_available)],
            ['Profit', Money::format($document->closing_profit)],
            ['Committed', Money::format($document->closing_committed)],
            ['Total', Money::format($document->closing_available + $document->closing_profit + $document->closing_committed)],
            ['File', $document->file_path],
            ['Size', number_format($document->file_bytes) . ' bytes'],
            ['SHA-256', $document->file_hash],
        ]);

        return self::SUCCESS;
    }
}
