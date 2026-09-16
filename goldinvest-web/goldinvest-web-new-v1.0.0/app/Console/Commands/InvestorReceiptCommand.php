<?php

namespace App\Console\Commands;

use App\Investor\Models\LedgerEntry;
use App\Investor\Services\DocumentIssuer;
use App\Investor\Support\Money;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Issues receipts for an investor's ledger movements.
 *
 * With no reference it issues one for every movement that does not have one
 * yet, which is how an existing account is brought up to date after this
 * feature is deployed.
 */
class InvestorReceiptCommand extends Command
{
    protected $signature = 'investor:receipt
                            {user : username or email}
                            {reference? : movement reference, e.g. GP-20260916-000001}
                            {--all : issue receipts for every movement without one}';

    protected $description = 'Generate investor receipt PDFs';

    public function handle(DocumentIssuer $issuer): int
    {
        $user = User::where('username', $this->argument('user'))
            ->orWhere('email', $this->argument('user'))
            ->first();

        if (! $user) {
            $this->error('No user matches ' . $this->argument('user'));

            return self::FAILURE;
        }

        $references = $this->argument('reference')
            ? [$this->argument('reference')]
            : ($this->option('all')
                ? LedgerEntry::forUser($user->id)->chronological()->pluck('reference')->unique()->values()->all()
                : []);

        if ($references === []) {
            $this->error('Name a movement reference, or pass --all.');

            return self::FAILURE;
        }

        $rows = [];

        foreach ($references as $reference) {
            try {
                $document = $issuer->receipt($user, $reference);
                $rows[] = [
                    $reference,
                    $document->document_number,
                    $document->title,
                    number_format($document->file_bytes) . ' B',
                    substr($document->file_hash, 0, 12) . '…',
                ];
            } catch (\Throwable $e) {
                $rows[] = [$reference, 'FAILED', $e->getMessage(), '', ''];
            }
        }

        $this->table(['Reference', 'Receipt', 'Type', 'Size', 'SHA-256'], $rows);

        return self::SUCCESS;
    }
}
