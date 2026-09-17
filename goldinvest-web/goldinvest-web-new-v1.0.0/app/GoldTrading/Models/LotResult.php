<?php

namespace App\GoldTrading\Models;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\AccountingPeriod;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a deal made.
 *
 * A row here is current until somebody records it, and final afterwards. That
 * difference is the whole point of the record: a deal whose gold has all been
 * sold can still have costs arriving, and a figure that might move is not one
 * the company can stand behind however complete it looks.
 *
 * Once `realized_at` is set the figures stop moving. Correcting one means
 * reversing the cost in the general ledger and recording the result again, so
 * the original and the correction are both visible — the same discipline the
 * ledger itself follows.
 */
class LotResult extends Model
{
    protected $table = 'gold_lot_results';

    protected $fillable = [
        'gold_lot_id', 'closed_at', 'refined_grams', 'sold_grams', 'cost_of_goods_sold_usd',
        'capitalised_cost_usd', 'expenses_usd', 'proceeds_usd', 'revenue_usd',
        'gross_profit_usd', 'net_profit_usd',
        'investor_share_percent', 'investor_profit_usd', 'company_profit_usd',
        'status', 'realized_at', 'realized_by', 'accounting_period_id',
        'notes', 'snapshot', 'approved_by',
    ];

    protected $casts = [
        'closed_at'              => 'date',
        'refined_grams'          => 'float',
        'sold_grams'             => 'float',
        'cost_of_goods_sold_usd' => 'float',
        'expenses_usd'           => 'float',
        'proceeds_usd'           => 'float',
        'gross_profit_usd'       => 'float',
        'net_profit_usd'         => 'float',
        'investor_share_percent' => 'float',
        'investor_profit_usd'    => 'float',
        'company_profit_usd'     => 'float',
        'capitalised_cost_usd'   => 'float',
        'revenue_usd'            => 'float',
        'realized_at'            => 'datetime',
        'snapshot'               => 'array',
    ];

    public const STATUS_DRAFT       = 'draft';
    public const STATUS_APPROVED    = 'approved';
    public const STATUS_REALIZED    = 'realized';
    public const STATUS_DISTRIBUTED = 'distributed';

    /**
     * What may still change once a result has been recorded.
     *
     * Only the investor side and the distribution that follows it: those are
     * Phase 3F's to write, and they are decisions about the result rather than
     * parts of it. Every accounting figure is fixed.
     */
    private const MUTABLE_AFTER_REALIZED = [
        'status', 'investor_share_percent', 'investor_profit_usd', 'company_profit_usd',
        'notes', 'approved_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $result) {
            // Nothing is fixed until somebody has stood behind it.
            if ($result->getOriginal('realized_at') === null) {
                return;
            }

            $changed = array_diff(array_keys($result->getDirty()), ['updated_at']);
            $refused = array_diff($changed, self::MUTABLE_AFTER_REALIZED);

            if ($refused !== []) {
                throw new PostingRefused(
                    'The result of deal #' . $result->gold_lot_id . ' has been recorded and its figures '
                    . 'cannot be altered (' . implode(', ', $refused) . '). Reverse the entry in the general '
                    . 'ledger and record the result again, so both are visible.'
                );
            }
        });
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function realizedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'realized_by');
    }

    /** Whether somebody has stood behind these figures. */
    public function isRealized(): bool
    {
        return $this->realized_at !== null;
    }

    /**
     * Whether these figures came out of the general ledger.
     *
     * Only the recorder sets `realized_at`, and it derives every figure from
     * posted journals, so this is the same question as whether the result was
     * recorded at all. A deal reconstructed from records that predate the ledger
     * does not have it set, and cannot pass for one that does.
     */
    public function fromLedger(): bool
    {
        return $this->realized_at !== null;
    }
}
