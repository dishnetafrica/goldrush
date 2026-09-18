@php use App\Investor\Support\Money; $pc = $period_close; @endphp
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Period Close Pack {{ $period->code }}</title>
@include('admin.sections.accounting-reports.pdf._styles')
</head>
<body>
<div class="footer">{{ $branding['name'] ?? '' }} - Period Close Pack {{ $reference }} - {{ $period->code }} - {{ $period->close_reference }} - FINAL</div>
<h1>Period Close Pack - {{ $period->code }}</h1>
<table class="hdr">
    <tr><td class="k">Period</td><td>{{ $period->label() }}</td><td class="k">Period status</td><td>{{ $period->status }}</td></tr>
    <tr><td class="k">Close reference</td><td>{{ $period->close_reference }}</td><td class="k">Closed by</td><td>{{ $period->closedBy?->username ?? 'console' }}</td></tr>
    <tr><td class="k">Closed at</td><td>{{ $period->closed_at?->format('d M Y H:i:s') }}</td><td class="k">Close reason</td><td>{{ $period->close_reason }}</td></tr>
    <tr><td class="k">Pack reference</td><td>{{ $reference }}</td><td class="k">Generated at</td><td>{{ $generated_at->format('d M Y H:i:s') }} by {{ $generated_by }}</td></tr>
    <tr><td class="k">Scope</td><td>{{ $scope->label() }}</td><td class="k">Interim / final</td><td><strong>FINAL</strong> - {{ $scope->status() }}</td></tr>
    <tr><td class="k">Control results</td><td colspan="3">{{ count($controls['exceptions']) }} control exception(s); {{ count($controls['pre_backfill']) }} pre-backfill difference(s) (D2/D3 open); {{ count($controls['controls']) }} controls evaluated</td></tr>
    <tr><td class="k">SHA-256</td><td colspan="3">recorded against this file on generation; the stored hash is checked on every download</td></tr>
</table>
<p class="rpt-note"><strong>This pack is a reporting snapshot, not an accounting record.</strong> The posted general ledger remains the authority; gold_lot_results is the recorded deal-result artifact shown as a control against it. Contents: {{ implode(', ', $sections) }}.</p>

<h2>1. Trial Balance</h2>
@include('admin.sections.accounting-reports._partials.trial-balance', ['data' => $trial_balance])

<h2 class="page-break">2. Profit and Loss</h2>
@include('admin.sections.accounting-reports._partials.profit-loss', ['data' => $profit_loss])

<h2 class="page-break">3. Balance Sheet</h2>
@include('admin.sections.accounting-reports._partials.balance-sheet', ['data' => $balance_sheet])

<h2 class="page-break">4. Cash Flow</h2>
@include('admin.sections.accounting-reports._partials.cash-flow', ['data' => $cash_flow])

<h2 class="page-break">5. Period Close Report</h2>
@include('admin.sections.accounting-reports._partials.period-close', ['data' => $pc])

<h2 class="page-break">6. Reconciliation and Controls</h2>
@include('admin.sections.accounting-reports._partials.controls-report', ['data' => $controls])

<h2 class="page-break">7. Allocation and Distribution</h2>
@include('admin.sections.accounting-reports._partials.distribution', ['data' => $pc])
</body></html>
