<?php

namespace App\Accounting\Reports;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Models\AccountingPeriod;
use Illuminate\Support\Carbon;

/**
 * What a report is about: one period, or a date range, as at a date.
 *
 * A scope is either a period or a range, never both, because a figure that
 * could have come from either is a figure nobody can check. Whether the
 * scope is interim or final follows from the period alone: a closed period
 * is final, everything else is interim, and a reopened period is interim
 * with its earlier close shown as history.
 */
final class ReportScope
{
    public function __construct(
        public readonly ?AccountingPeriod $period = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly ?string $asAt = null,
        public readonly ?string $sourcePeriod = null,
        public readonly ?string $lot = null,
        public readonly ?int $userId = null,
    ) {
    }

    /** @param array<string, mixed> $input  period|from|to|as_at|source_period|lot|user */
    public static function fromInput(array $input): self
    {
        $code = trim((string) ($input['period'] ?? ''));
        $from = self::date($input['from'] ?? null);
        $to = self::date($input['to'] ?? null);
        $asAt = self::date($input['as_at'] ?? null);

        if ($code !== '' && ($from || $to)) {
            throw new AccountingException('Give a period or a date range, not both.');
        }

        if ($from && $to && $from > $to) {
            throw new AccountingException('The range ends before it starts.');
        }

        $period = null;

        if ($code !== '') {
            $period = AccountingPeriod::where('code', $code)->first()
                ?? throw new AccountingException('No accounting period with code ' . $code . '.');
        }

        return new self(
            $period, $from, $to, $asAt,
            ($input['source_period'] ?? null) ? trim((string) $input['source_period']) : null,
            ($input['lot'] ?? null) ? trim((string) $input['lot']) : null,
            isset($input['user']) && $input['user'] !== '' ? (int) $input['user'] : null,
        );
    }

    public static function forPeriod(AccountingPeriod $period): self
    {
        return new self($period);
    }

    /** Everything to a date: the balance sheet's scope. */
    public static function asAt(?string $asAt): self
    {
        return new self(null, null, null, self::date($asAt));
    }

    /** The shape RealizedTradingResult and the ledger readers take. */
    public function ledgerScope(): array
    {
        if ($this->period) {
            return ['period_id' => $this->period->id];
        }

        return array_filter(['from' => $this->from, 'to' => $this->to ?? $this->asAt]);
    }

    public function start(): ?Carbon
    {
        if ($this->period) {
            return $this->period->starts_on->copy()->startOfDay();
        }

        return $this->from ? Carbon::parse($this->from)->startOfDay() : null;
    }

    public function end(): Carbon
    {
        if ($this->period) {
            return $this->period->ends_on->copy()->endOfDay();
        }

        $end = $this->to ?? $this->asAt;

        return $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfDay();
    }

    /** The day before the scope starts, for opening balances; null means "from the beginning". */
    public function openingDate(): ?string
    {
        $start = $this->start();

        return $start?->subDay()->toDateString();
    }

    public function isPeriod(): bool
    {
        return $this->period !== null;
    }

    public function isFinal(): bool
    {
        return $this->period !== null && $this->period->isClosed();
    }

    public function label(): string
    {
        if ($this->period) {
            return $this->period->label();
        }

        if ($this->from || $this->to) {
            return ($this->from ?? 'the beginning') . ' to ' . ($this->to ?? 'today');
        }

        if ($this->asAt) {
            return 'as at ' . $this->asAt;
        }

        return 'all periods to date';
    }

    /** INTERIM or FINAL, and why. */
    public function status(): string
    {
        if ($this->period === null) {
            return 'INTERIM - ' . ($this->from || $this->to ? 'date range, not a closed period' : 'to date, not a closed period');
        }

        if ($this->period->isClosed()) {
            return 'FINAL - period closed as ' . $this->period->close_reference;
        }

        if ($this->period->close_reference !== null) {
            return 'INTERIM - period reopened (was closed as ' . $this->period->close_reference . ' on '
                . $this->period->closed_at?->format('d M Y') . '; reopened '
                . $this->period->reopened_at?->format('d M Y') . ': ' . $this->period->reopen_reason . ')';
        }

        return 'INTERIM - period ' . $this->period->status;
    }

    public function toArray(): array
    {
        return array_filter([
            'period'        => $this->period?->code,
            'period_status' => $this->period?->status,
            'from'          => $this->from,
            'to'            => $this->to,
            'as_at'         => $this->asAt,
            'source_period' => $this->sourcePeriod,
            'lot'           => $this->lot,
            'user'          => $this->userId,
            'label'         => $this->label(),
            'status'        => $this->status(),
        ], fn ($v) => $v !== null);
    }

    private static function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Carbon::parse($value)->toDateString();
    }
}
