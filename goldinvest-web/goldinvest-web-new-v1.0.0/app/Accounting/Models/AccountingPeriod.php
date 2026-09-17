<?php

namespace App\Accounting\Models;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A month of trading.
 *
 * A period is where postings live and where closing happens. Once closed, the
 * figures it contains are the ones the company stands behind, which is why
 * nothing may be posted into it afterwards — and why a distribution may only be
 * made from a period that has reached that state.
 */
class AccountingPeriod extends Model
{
    protected $table = 'accounting_periods';

    public const OPEN      = 'open';
    public const IN_REVIEW = 'in_review';
    public const CLOSED    = 'closed';
    public const LOCKED    = 'locked';

    protected $fillable = [
        'code', 'starts_on', 'ends_on', 'status', 'closed_by', 'closed_at',
        'locked_at', 'reopened_by', 'reopened_at', 'reopen_reason',
    ];

    protected $casts = [
        'starts_on'   => 'date',
        'ends_on'     => 'date',
        'closed_at'   => 'datetime',
        'locked_at'   => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'accounting_period_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'closed_by');
    }

    /**
     * Whether ordinary postings are still allowed.
     *
     * A period under review still accepts entries, because review is exactly
     * when adjustments are found. Closed and locked do not.
     */
    public function acceptsPostings(): bool
    {
        return in_array($this->status, [self::OPEN, self::IN_REVIEW], true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::CLOSED, self::LOCKED], true);
    }

    /** The period a given date falls in, or null if none has been created yet. */
    public static function covering(Carbon|string $date): ?self
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return static::whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->first();
    }

    public function label(): string
    {
        return $this->code . ' (' . $this->starts_on->format('d M Y') . ' - ' . $this->ends_on->format('d M Y') . ')';
    }
}
