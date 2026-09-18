<?php

namespace App\Accounting\Models;

use App\Accounting\Exceptions\PostingRefused;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The act of distributing a period's allocation to its investors.
 *
 * Immutable once written. Its figures are the ones the company owed and the
 * investors were credited; a distribution that turned out to be wrong is
 * reversed, with both entries left visible, and the period distributed again.
 * The only fields that may change after posting are the ones that record that
 * reversal.
 */
class InvestorDistribution extends Model
{
    protected $table = 'investor_distributions';

    public const POSTED   = 'posted';
    public const REVERSED = 'reversed';

    protected $fillable = [
        'reference', 'accounting_period_id', 'journal_id', 'company_result_usd', 'gross_pool_usd',
        'reserve_usd', 'pool_usd', 'investors_count', 'status', 'active_seq', 'snapshot',
        'distributed_by', 'distributed_at', 'reversal_journal_id', 'reversed_by', 'reversed_at',
        'reversal_reason',
    ];

    protected $casts = [
        'company_result_usd' => 'float',
        'gross_pool_usd'     => 'float',
        'reserve_usd'        => 'float',
        'pool_usd'           => 'float',
        'investors_count'    => 'integer',
        'active_seq'         => 'integer',
        'snapshot'           => 'array',
        'distributed_at'     => 'datetime',
        'reversed_at'        => 'datetime',
    ];

    private const MUTABLE_AFTER_POST = [
        'status', 'active_seq', 'reversal_journal_id', 'reversed_by', 'reversed_at', 'reversal_reason',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $distribution) {
            $changed = array_diff(array_keys($distribution->getDirty()), ['updated_at']);
            $refused = array_diff($changed, self::MUTABLE_AFTER_POST);

            if ($refused !== []) {
                throw new PostingRefused(
                    'Distribution ' . $distribution->reference . ' has been posted and cannot be altered ('
                    . implode(', ', $refused) . '). Reverse it and distribute the period again.'
                );
            }

            if ($distribution->getOriginal('status') === self::REVERSED) {
                throw new PostingRefused('Distribution ' . $distribution->reference . ' has already been reversed.');
            }
        });

        static::deleting(function (self $distribution) {
            throw new PostingRefused('Distribution ' . $distribution->reference . ' cannot be deleted.');
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvestorDistributionLine::class, 'investor_distribution_id');
    }

    public function distributedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'distributed_by');
    }
}
