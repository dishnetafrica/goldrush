@php use App\Investor\Support\Money; $pc = $data; $p = $pc['period']; @endphp
@include('admin.sections.accounting-reports._partials.banner', ['data' => $pc])
<table class="rpt">
    <tbody>
    <tr><td>Period</td><td>{{ $p['label'] }}</td></tr>
    <tr><td>Status</td><td>{{ $p['status'] }}</td></tr>
    <tr><td>Close reference</td><td>{{ $p['close_reference'] ?? '-' }}</td></tr>
    <tr><td>Closed by / at</td><td>{{ $p['closed_by'] ?? '-' }} / {{ $p['closed_at'] ?? '-' }}</td></tr>
    <tr><td>Close reason</td><td>{{ $p['close_reason'] ?? '-' }}</td></tr>
    @if ($p['reopened_at'])<tr><td>Reopened</td><td>{{ $p['reopened_at'] }} - {{ $p['reopen_reason'] }} (the close above is kept as history)</td></tr>@endif
    <tr><td>Revenue / COGS / expenses</td><td>{{ Money::format($pc['result']['ledger_revenue_usd']) }} / {{ Money::format($pc['result']['ledger_cogs_usd']) }} / {{ Money::format($pc['result']['ledger_expenses_usd']) }}</td></tr>
    <tr class="total"><td>Realized trading result</td><td>{{ Money::format($pc['result']['operating_result_usd']) }} (deals {{ Money::format($pc['result']['trading_result_usd']) }}, overheads {{ Money::format($pc['result']['unattributed_expenses_usd']) }})</td></tr>
    <tr><td>Investor allocation</td><td>pool {{ Money::format($pc['allocation']['investor_pool_usd']) }}; {{ $pc['allocation']['distributable'] ? 'distributable' : (implode('; ', $pc['allocation']['refusals']) ?: 'nothing to distribute') }}</td></tr>
    <tr><td>Distributions</td><td>@forelse ($pc['distributions'] as $d){{ $d['reference'] }} {{ $d['status'] }} {{ Money::format($d['pool_usd']) }}, journal {{ $d['journal'] }} dated {{ $d['journal_date'] }} in {{ $d['declared_in'] }}, {{ $d['investors'] }} investor(s)@if ($d['reversed_at']); reversed {{ $d['reversed_at'] }}: {{ $d['reversal_reason'] }}@endif<br>@empty none @endforelse</td></tr>
    <tr><td>2010 at period end / now</td><td>{{ Money::format($pc['payable_2010']['at_period_end']) }} / {{ Money::format($pc['payable_2010']['now']) }}<br><small>{{ $pc['payable_2010']['note'] }}</small></td></tr>
    <tr><td>Trial balance</td><td>debits {{ Money::exact($pc['trial_balance']['debits']) }} credits {{ Money::exact($pc['trial_balance']['credits']) }} - {{ $pc['trial_balance']['balanced'] ? 'balanced' : 'UNBALANCED' }}</td></tr>
    </tbody>
</table>
@if ($pc['blockers'])
    <h4>Blockers</h4><ul>@foreach ($pc['blockers'] as $b)<li>{{ $b }}</li>@endforeach</ul>
@endif
@if ($pc['pending_expenses'])
    <h4>Expenses dated in the period not yet posted</h4>
    <table class="rpt"><tbody>@foreach ($pc['pending_expenses'] as $e)<tr><td>{{ $e['reference'] }}</td><td>{{ $e['status'] }}</td><td>{{ $e['description'] }}</td><td class="n">{{ Money::format($e['amount_usd']) }}</td></tr>@endforeach</tbody></table>
@endif
@if ($pc['unmatched_bank_lines'])
    <h4>Unmatched bank lines</h4>
    <table class="rpt"><tbody>@foreach ($pc['unmatched_bank_lines'] as $l)<tr><td>{{ $l['date'] }}</td><td>{{ $l['description'] }}</td><td class="n">{{ Money::format($l['amount']) }}</td></tr>@endforeach</tbody></table>
@endif
<h4>Deals in the period</h4>
<table class="rpt">
    <thead><tr><th>Deal</th><th class="n">Revenue</th><th class="n">COGS</th><th class="n">Expenses</th><th class="n">Result</th><th>Stage</th></tr></thead>
    <tbody>
    @forelse ($pc['result']['lots'] as $l)
        <tr><td>{{ $l['lot_code'] }}</td><td class="n">{{ Money::format($l['revenue_usd']) }}</td><td class="n">{{ Money::format($l['cost_of_goods_sold_usd']) }}</td>
            <td class="n">{{ Money::format($l['ordinary_expenses_usd']) }}</td><td class="n">{{ Money::format($l['net_realized_usd']) }}</td><td>{{ $l['stage'] }}</td></tr>
    @empty
        <tr><td colspan="6">No deal has ledger activity in this period.</td></tr>
    @endforelse
    </tbody>
</table>
@if ($pc['snapshot'])
    <h4>Close snapshot against the ledger now</h4>
    @include('admin.sections.accounting-reports._partials.controls', ['controls' => $pc['snapshot_checks']])
@endif
<h4>Controls</h4>
@include('admin.sections.accounting-reports._partials.controls', ['controls' => array_slice($pc['controls'], 0, 3)])
