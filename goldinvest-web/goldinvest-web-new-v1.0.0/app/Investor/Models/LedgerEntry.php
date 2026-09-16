<?php

namespace App\Investor\Models;

use App\GoldTrading\Models\GoldLot;
use App\Investor\Exceptions\LedgerException;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\Flow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One leg of one movement in an investor's ledger.
 *
 * Append only. The model refuses to update or delete itself, because the whole
 * value of this table is that what it says today it will still say next year.
 * Corrections are new entries that reference the ones they correct.
 *
 * Note that this guard binds the application, not the database. Someone with a
 * MySQL prompt can still rewrite history; if that matters, add a BEFORE UPDATE
 * trigger as well.
 */
class LedgerEntry extends Model
{
    protected $table = 'investor_ledger_entries';

    protected $fillable = [
        'user_id', 'seq', 'occurred_at', 'entry_date', 'event_type', 'bucket',
        'amount_usd', 'flow', 'group_uuid', 'balance_available', 'balance_profit',
        'balance_committed', 'reference', 'trx_id', 'transaction_id', 'gold_lot_id',
        'allocation_id', 'reverses_entry_id', 'description', 'meta',
    ];

    protected $casts = [
        'occurred_at'       => 'datetime',
        'entry_date'        => 'date',
        'amount_usd'        => 'float',
        'balance_available' => 'float',
        'balance_profit'    => 'float',
        'balance_committed' => 'float',
        'seq'               => 'integer',
        'meta'              => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $entry) {
            throw new LedgerException(
                'Ledger entries are append only. Entry #' . $entry->id
                . ' cannot be changed; post a correcting or reversing movement instead.'
            );
        });

        static::deleting(function (self $entry) {
            throw new LedgerException(
                'Ledger entries are append only. Entry #' . $entry->id
                . ' cannot be deleted; post a reversing movement instead.'
            );
        });
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }

    /** The position across all three buckets immediately after this leg. */
    public function position(): array
    {
        return [
            Bucket::AVAILABLE => $this->balance_available,
            Bucket::PROFIT    => $this->balance_profit,
            Bucket::COMMITTED => $this->balance_committed,
        ];
    }

    public function totalPosition(): float
    {
        return round($this->balance_available + $this->balance_profit + $this->balance_committed, 8);
    }

    public function isExternal(): bool
    {
        return in_array($this->flow, [Flow::EXTERNAL_IN, Flow::EXTERNAL_OUT], true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeChronological($query)
    {
        return $query->orderBy('seq');
    }
}
