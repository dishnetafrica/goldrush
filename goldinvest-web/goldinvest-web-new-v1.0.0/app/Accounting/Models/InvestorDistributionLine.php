<?php

namespace App\Accounting\Models;

use App\Accounting\Exceptions\PostingRefused;
use App\Investor\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One investor's share of one distribution, and where it went.
 *
 * Points at the transaction the application sees and the ledger entry that
 * records it. It does not hold a balance of its own; the balance is the
 * ledger's, and this only says which entry this distribution wrote.
 */
class InvestorDistributionLine extends Model
{
    protected $table = 'investor_distribution_lines';

    protected $fillable = [
        'investor_distribution_id', 'user_id', 'amount_usd', 'trx_id', 'transaction_id',
        'ledger_entry_id', 'ledger_reference', 'snapshot',
        'reversal_transaction_id', 'reversal_ledger_entry_id',
    ];

    protected $casts = [
        'amount_usd' => 'float',
        'snapshot'   => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $line) {
            $changed = array_diff(array_keys($line->getDirty()), ['updated_at']);
            $refused = array_diff($changed, ['reversal_transaction_id', 'reversal_ledger_entry_id']);

            if ($refused !== []) {
                throw new PostingRefused(
                    'Distribution line #' . $line->id . ' has been posted and cannot be altered ('
                    . implode(', ', $refused) . ').'
                );
            }
        });

        static::deleting(function (self $line) {
            throw new PostingRefused('Distribution line #' . $line->id . ' cannot be deleted.');
        });
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(InvestorDistribution::class, 'investor_distribution_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }
}
