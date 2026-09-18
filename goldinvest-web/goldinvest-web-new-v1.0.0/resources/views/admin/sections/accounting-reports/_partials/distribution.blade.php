@php use App\Investor\Support\Money; $pc = $data; $a = $pc['allocation']; @endphp
<table class="rpt">
    <tbody>
    <tr><td>Policy</td><td>{{ $a['policy'] }}</td></tr>
    <tr><td>Company realized trading result</td><td class="n">{{ Money::format($a['company_trading_result_usd']) }}</td></tr>
    <tr><td>Overheads belonging to no deal (excluded)</td><td class="n">{{ Money::format($a['company_overheads_excluded_usd']) }}</td></tr>
    <tr><td>Eligible realized results</td><td class="n">{{ Money::format($a['eligible_realized_usd']) }}</td></tr>
    <tr><td>Gross investor pool</td><td class="n">{{ Money::format($a['gross_investor_pool_usd']) }}</td></tr>
    <tr><td>Reserve ({{ number_format($a['reserve_percent'], 2) }}%)</td><td class="n">{{ Money::format($a['reserve_usd']) }}</td></tr>
    <tr class="total"><td>Investor allocation pool</td><td class="n">{{ Money::format($a['investor_pool_usd']) }}</td></tr>
    <tr><td>Company retains</td><td class="n">{{ Money::format($a['company_retains_usd']) }}</td></tr>
    <tr><td>Loss policy</td><td>{{ $a['loss_policy_status'] }}</td></tr>
    </tbody>
</table>
@if ($a['eligible'])
<table class="rpt">
    <thead><tr><th>Deal</th><th class="n">Realized</th><th>Policy</th><th class="n">Pool basis</th><th class="n">Share %</th><th class="n">To investors</th><th class="n">Company keeps</th></tr></thead>
    <tbody>@foreach ($a['eligible'] as $d)<tr><td>{{ $d['lot_code'] }}</td><td class="n">{{ Money::format($d['realized_usd']) }}</td><td>{{ $d['expense_policy'] }}</td>
        <td class="n">{{ Money::format($d['pool_basis_usd']) }}</td><td class="n">{{ number_format($d['investor_share_pct'], 2) }}</td><td class="n">{{ Money::format($d['investor_usd']) }}</td><td class="n">{{ Money::format($d['company_usd']) }}</td></tr>@endforeach</tbody>
</table>
@endif
@foreach ($a['ineligible'] as $i)<p class="rpt-note">not eligible - {{ $i['lot_code'] }}: {{ $i['reason'] }}</p>@endforeach
@foreach ($a['blocked'] as $b)<p class="rpt-note ctl-ex">BLOCKED - {{ $b['lot_code'] }}: {{ $b['reason'] }}</p>@endforeach
@forelse ($pc['distributions'] as $d)
    <h4>{{ $d['reference'] }} - {{ $d['status'] }}</h4>
    <p class="rpt-note">Journal {{ $d['journal'] }} dated {{ $d['journal_date'] }} in {{ $d['declared_in'] }}: Dr 7000 / Cr 2010 {{ Money::exact($d['pool_usd']) }}@if ($d['reversed_at']); reversed {{ $d['reversed_at'] }} - {{ $d['reversal_reason'] }}@endif</p>
    <table class="rpt"><thead><tr><th>Investor</th><th class="n">Credited</th><th>Transaction</th><th>Ledger entry</th></tr></thead>
        <tbody>@foreach ($d['lines'] as $l)<tr><td>{{ $l['user'] }}</td><td class="n">{{ Money::exact($l['amount_usd']) }}</td><td>{{ $l['trx_id'] }}</td><td>{{ $l['ledger_reference'] }}</td></tr>@endforeach</tbody></table>
@empty
    <p class="rpt-note">No distribution has been posted for this period.</p>
@endforelse
