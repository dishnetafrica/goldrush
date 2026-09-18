<?php

namespace App\Accounting\Reports;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\PeriodClosePack;
use App\Accounting\Security\AccountingPermission;
use App\Investor\Services\SequenceAllocator;
use App\Investor\Support\Branding;
use App\Models\Admin\Admin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One PDF per period close: trial balance, profit and loss, balance sheet,
 * cash flow, the close report, the controls, and the allocation and
 * distribution where they exist.
 *
 * A snapshot, not an accounting record. It is generated once per close
 * reference, stored on the private disk, hashed, and handed back unchanged
 * on every later request. A reopened and re-closed period has a new close
 * reference and therefore a new pack; the old one stays where it was.
 */
class ClosePackBuilder
{
    public const SECTIONS = [
        'trial-balance', 'profit-loss', 'balance-sheet', 'cash-flow', 'period-close', 'controls', 'distribution',
    ];

    public function __construct(
        private readonly FinancialStatements $statements,
        private readonly TradingReports $trading,
        private readonly ControlReports $controls,
        private readonly SequenceAllocator $sequences,
    ) {
    }

    public function existing(AccountingPeriod $period): ?PeriodClosePack
    {
        if ($period->close_reference === null) {
            return null;
        }

        $pack = PeriodClosePack::where('accounting_period_id', $period->id)
            ->where('close_reference', $period->close_reference)->first();

        return $pack && $pack->intact() ? $pack : null;
    }

    /** The figures the pack is rendered from, so a screen can show the same thing. */
    public function data(AccountingPeriod $period): array
    {
        $scope = ReportScope::forPeriod($period);

        return [
            'period'        => $period,
            'scope'         => $scope,
            'trial_balance' => $this->statements->trialBalance($scope, true),
            'profit_loss'   => $this->statements->profitAndLoss($scope),
            'balance_sheet' => $this->statements->balanceSheet($scope),
            'cash_flow'     => $this->statements->cashFlow($scope),
            'period_close'  => $this->trading->periodClose($period),
            'controls'      => $this->controls->all($scope),
        ];
    }

    public function build(AccountingPeriod $period, ?Admin $actor = null): PeriodClosePack
    {
        AccountingPermission::assert($actor, AccountingPermission::REPORT_EXPORT);

        $period->refresh();

        if (! $period->isClosed()) {
            throw new PostingRefused('Period ' . $period->code . ' is ' . $period->status
                . '; a close pack documents a close, and there is none to document.');
        }

        if ($existing = $this->existing($period)) {
            return $existing;
        }

        $data = $this->data($period);
        $controls = $data['controls']['controls'];

        return DB::transaction(function () use ($period, $actor, $data, $controls) {
            $reference = $this->sequences->next('PCK', Carbon::now());
            $generatedAt = Carbon::now();

            $html = view('admin.sections.accounting-reports.pdf.pack', $data + [
                'reference'    => $reference,
                'generated_at' => $generatedAt,
                'generated_by' => $actor?->username ?? 'console',
                'branding'     => Branding::get(),
                'sections'     => self::SECTIONS,
            ])->render();

            $pdf = Pdf::loadHTML($html)->setPaper('a4')->output();
            $path = 'close-packs/' . $period->code . '/' . $reference . '.pdf';

            Storage::disk(PeriodClosePack::DISK)->put($path, $pdf);

            return PeriodClosePack::create([
                'reference'            => $reference,
                'accounting_period_id' => $period->id,
                'close_reference'      => $period->close_reference,
                'file_path'            => $path,
                'file_hash'            => hash('sha256', $pdf),
                'file_bytes'           => strlen($pdf),
                'controls'             => array_map(fn ($c) => ['key' => $c['key'], 'status' => $c['status'], 'difference' => $c['difference']], $controls),
                'controls_passed'      => Control::allPassed($controls),
                'sections'             => self::SECTIONS,
                'generated_by'         => $actor?->username ?? 'console',
                'generated_at'         => $generatedAt,
            ]);
        });
    }
}
