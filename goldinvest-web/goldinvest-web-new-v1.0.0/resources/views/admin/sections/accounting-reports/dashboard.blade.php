@extends('admin.layouts.master')
@php use App\Investor\Support\Money; @endphp
@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        ['name' => __("Dashboard"), 'url' => setRoute("admin.dashboard")],
    ], 'active' => __("Accounting Reports")])
@endsection
@section('content')
@include('admin.sections.accounting-reports._partials._styles')
<div class="dashboard-area">
    <div class="dashboard-header-wrapper">
        <h4 class="title">{{ __("Accounting Dashboard") }} <small class="text-muted">as at {{ $d['as_at'] }}</small></h4>
        <div>
            @foreach (['trial-balance' => 'Trial Balance', 'profit-loss' => 'P&L', 'balance-sheet' => 'Balance Sheet', 'cash-flow' => 'Cash Flow', 'gold-trading' => 'Gold', 'investor-liability' => 'Investors', 'controls' => 'Controls'] as $r => $l)
                <a href="{{ setRoute('admin.accounting.report.' . $r) }}" class="btn btn-sm btn--base">{{ __($l) }}</a>
            @endforeach
        </div>
    </div>
    @if ($d['empty'])
        <div class="alert alert-warning mt-10">{{ __("The ledger holds no journals yet. Every figure below is zero because nothing has been posted.") }}</div>
    @endif
    <div class="row mt-10">
        @php $tiles = [
            ['Cash on hand', Money::format($d['cash']['cash_on_hand_usd']), '1000-series balances', 'balance-sheet'],
            ['Bank', Money::format($d['cash']['bank_usd']), '1010-series balances', 'balance-sheet'],
            ['1090 Suspense', Money::format($d['suspense']['balance']), $d['suspense']['caption'], 'balance-sheet'],
            ['Gold held', number_format($d['gold']['remaining_grams'], 4) . ' g at cost ' . Money::format($d['gold']['inventory_usd']), 'LotCostBasis; GL 1100+1110 ' . Money::format($d['gold']['gl_1100_usd'] + $d['gold']['gl_1110_usd']), 'gold-trading'],
            ['Open period result', $d['open_period'] ? $d['open_period']['code'] . ': ' . Money::format($d['open_period']['trading_result_usd']) : '-', $d['open_period'] ? $d['open_period']['status_label'] : 'no open period', 'profit-loss'],
            ['Last closed period', $d['last_closed'] ? $d['last_closed']['code'] . ': ' . Money::format($d['last_closed']['trading_result_usd']) : 'none', $d['last_closed'] ? $d['last_closed']['close_reference'] . ' (close snapshot)' : '', 'profit-loss'],
            ['Investor capital payable', Money::format($d['investor_liability']['capital_2000_usd']), 'GL 2000', 'investor-liability'],
            ['Investor profit payable', Money::format($d['investor_liability']['profit_2010_usd']), 'GL 2010', 'investor-liability'],
            ['Outstanding expenses', $d['expenses_outstanding']['count'] . ' / ' . Money::format($d['expenses_outstanding']['total_usd']), 'draft, submitted, approved - not in the P&L', 'controls'],
            ['Deals awaiting', 'costs ' . count($d['awaiting']['costs_to_finalise']) . ', record ' . count($d['awaiting']['results_to_record']) . ', allocation blocked ' . count($d['awaiting']['allocation_blocked']), 'RealizedTradingResult / InvestorAllocation', 'gold-trading'],
            ['Ledger health', $d['ledger_health']['investors'] . ' investor(s), ' . $d['ledger_health']['failing'] . ' failing, ' . $d['ledger_health']['wallet_mismatch'] . ' wallet mismatch', 'ledger:check', 'investor-liability'],
            ['Historical deals', $d['historical_deals'], 'historical attribution - not posted to company GL', 'gold-trading'],
        ]; @endphp
        @foreach ($tiles as [$t, $v, $src, $link])
            <div class="col-md-3 mb-3"><a href="{{ setRoute('admin.accounting.report.' . $link) }}" style="text-decoration:none;color:inherit;"><div class="card"><div class="card-body">
                <span class="text-muted">{{ __($t) }}</span><h5>{{ $v }}</h5><small class="text-muted">{{ $src }}</small></div></div></a></div>
        @endforeach
    </div>
    <div class="custom-card mt-10"><div class="card-body table-responsive">
        <h5>{{ __("Bank and cash accounts") }}</h5>
        <table class="rpt"><thead><tr><th>Account</th><th>Type</th><th class="n">Ledger balance</th><th class="n">Unmatched lines</th><th>Last reconciled</th></tr></thead>
        <tbody>@forelse ($d['banks'] as $b)<tr><td>{{ $b['code'] }} {{ $b['name'] }}</td><td>{{ $b['type'] }}</td><td class="n">{{ Money::format($b['balance_usd']) }}</td>
            <td class="n {{ $b['unmatched'] ? 'ctl-ex' : '' }}">{{ $b['unmatched'] }}</td><td>{{ $b['last_reconciled'] ?? 'never' }}</td></tr>@empty<tr><td colspan="5">No cash or bank account is defined.</td></tr>@endforelse</tbody></table>
        <p class="text-muted mb-0" style="font-size:12px;">{{ __("Every tile names its source and links to the report it summarises. Nothing is computed on this page.") }}</p>
    </div></div>
</div>
@endsection
