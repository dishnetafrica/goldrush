<?php

namespace App\Http\Controllers\Admin;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\PeriodClosePack;
use App\Accounting\Reports\ClosePackBuilder;
use App\Accounting\Reports\ControlReports;
use App\Accounting\Reports\CsvExport;
use App\Accounting\Reports\FinancialStatements;
use App\Accounting\Reports\InvestorLiabilityReport;
use App\Accounting\Reports\ReportAudit;
use App\Accounting\Reports\ReportScope;
use App\Accounting\Reports\TradingReports;
use App\Accounting\Security\AccountingPermission;
use App\Http\Controllers\Controller;
use App\Investor\Support\Branding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 3G: the accounting reports.
 *
 * Every action here reads. The only writes are the audit row that says the
 * report was rendered and, for a closed period, the close pack. Permission is
 * checked twice: the vendor's route grant, and the accounting token, so a
 * route granted by mistake still shows nothing.
 */
class AccountingReportController extends Controller
{
    public function __construct(
        private readonly FinancialStatements $statements,
        private readonly TradingReports $trading,
        private readonly InvestorLiabilityReport $investors,
        private readonly ControlReports $controls,
        private readonly ClosePackBuilder $packs,
        private readonly ReportAudit $audit,
        private readonly CsvExport $csv,
    ) {
    }

    public function dashboard()
    {
        AccountingPermission::assert(auth()->user(), AccountingPermission::DASHBOARD_VIEW);

        $data = $this->trading->dashboard();
        $this->audit->record('dashboard', ['as_at' => $data['as_at']], [], true, auth()->user());

        return view('admin.sections.accounting-reports.dashboard', ['page_title' => __('Accounting Dashboard'), 'd' => $data]);
    }

    public function trialBalance(Request $request)
    {
        return $this->render($request, AccountingPermission::REPORT_VIEW, 'Trial Balance', 'trial-balance',
            fn (ReportScope $s) => $this->statements->trialBalance($s, $request->boolean('show_zero')));
    }

    public function profitLoss(Request $request)
    {
        return $this->render($request, AccountingPermission::REPORT_VIEW, 'Profit and Loss', 'profit-loss',
            fn (ReportScope $s) => $this->statements->profitAndLoss($s));
    }

    public function balanceSheet(Request $request)
    {
        return $this->render($request, AccountingPermission::REPORT_VIEW, 'Balance Sheet', 'balance-sheet',
            fn (ReportScope $s) => $this->statements->balanceSheet($s));
    }

    public function cashFlow(Request $request)
    {
        return $this->render($request, AccountingPermission::REPORT_VIEW, 'Cash Flow', 'cash-flow',
            fn (ReportScope $s) => $this->statements->cashFlow($s));
    }

    public function goldTrading(Request $request)
    {
        return $this->render($request, AccountingPermission::REPORT_VIEW, 'Gold Trading / Lot Performance', 'gold-trading',
            fn (ReportScope $s) => $this->trading->goldTrading($s));
    }

    public function investorLiability(Request $request)
    {
        return $this->render($request, AccountingPermission::REPORT_INVESTOR, 'Investor Liability', 'investor-liability',
            fn (ReportScope $s) => $this->investors->company($s));
    }

    public function periodClose(Request $request, string $period)
    {
        $request->merge(['period' => $period]);

        return $this->render($request, AccountingPermission::REPORT_VIEW, 'Period Close Report', 'period-close',
            fn (ReportScope $s) => $this->trading->periodClose($s->period), $period);
    }

    public function controls(Request $request)
    {
        return $this->render($request, AccountingPermission::CONTROL_VIEW, 'Reconciliation and Controls', 'controls',
            fn (ReportScope $s) => $this->controls->all($s));
    }

    public function packStore(string $period)
    {
        AccountingPermission::assert(auth()->user(), AccountingPermission::REPORT_EXPORT);

        $p = AccountingPeriod::where('code', $period)->firstOrFail();

        try {
            $pack = $this->packs->build($p, auth()->user());
        } catch (\Throwable $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }

        $this->audit->record('close-pack', ['period' => $p->code], [], false, auth()->user(), 'pdf', $pack->file_hash);

        return back()->with(['success' => [__('Close pack') . ' ' . $pack->reference . ' (sha256 ' . substr($pack->file_hash, 0, 12) . '...)']]);
    }

    public function packDownload(int $pack)
    {
        AccountingPermission::assert(auth()->user(), AccountingPermission::REPORT_EXPORT);

        $p = PeriodClosePack::findOrFail($pack);

        abort_unless($p->intact(), 409, 'The stored close pack no longer matches its recorded hash.');

        return Storage::disk(PeriodClosePack::DISK)->download($p->file_path, $p->reference . '.pdf');
    }

    /** Render a report to the screen, a PDF or a CSV, and record that it was. */
    private function render(Request $request, string $token, string $title, string $partial, \Closure $build, ?string $periodCode = null)
    {
        AccountingPermission::assert(auth()->user(), $token);

        try {
            $scope = ReportScope::fromInput($request->only(['period', 'from', 'to', 'as_at', 'source_period', 'lot', 'user']));
            $data = $build($scope);
        } catch (AccountingException $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }

        $format = $request->string('format')->toString();

        if ($format !== '') {
            AccountingPermission::assert(auth()->user(), AccountingPermission::REPORT_EXPORT);

            if ($format === 'csv') {
                $csv = $this->csv->string($data);
                $this->audit->record($partial, $data['scope'] ?? [], $data['controls'] ?? [], ! $scope->isFinal(), auth()->user(), 'csv', hash('sha256', $csv));

                return response($csv, 200, [
                    'Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $partial . '.csv"',
                ]);
            }

            $html = view('admin.sections.accounting-reports.pdf.report', [
                'title' => $title, 'partial' => $partial, 'data' => $data, 'scope' => $scope, 'branding' => Branding::get(),
                'generated_by' => auth()->user()?->username ?? 'admin',
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4')->output();
            $this->audit->record($partial, $data['scope'] ?? [], $data['controls'] ?? [], ! $scope->isFinal(), auth()->user(), 'pdf', hash('sha256', $pdf));

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="' . $partial . '.pdf"',
            ]);
        }

        $this->audit->record($partial, $data['scope'] ?? [], $data['controls'] ?? [], ! $scope->isFinal(), auth()->user());

        $periods = AccountingPeriod::orderByDesc('code')->get(['code', 'status', 'close_reference']);
        $packs = $scope->period ? PeriodClosePack::where('accounting_period_id', $scope->period->id)->orderByDesc('id')->get() : collect();

        return view('admin.sections.accounting-reports.report', [
            'page_title' => __($title), 'title' => $title, 'partial' => $partial, 'data' => $data, 'scope' => $scope,
            'periods' => $periods, 'packs' => $packs, 'input' => $request->only(['period', 'from', 'to', 'as_at', 'source_period', 'lot', 'user', 'show_zero']),
            'routeName' => 'admin.accounting.report.' . $partial, 'periodCode' => $periodCode,
        ]);
    }
}
