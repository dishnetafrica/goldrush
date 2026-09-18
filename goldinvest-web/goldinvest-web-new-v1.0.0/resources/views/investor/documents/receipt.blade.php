@php
    use App\Investor\Support\Money;
    use App\Investor\Ledger\Bucket;
    $r = $receipt;
    $c = $r['currency'];
@endphp
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $r['title'] }}</title>
@include('investor.documents._styles')
</head>
<body>

@include('investor.documents._header', ['title' => $r['title']])

<table style="margin-top:4px;">
    <tr>
        <td style="width:55%; vertical-align:top;">
            <table class="kv">
                <tr><td class="k">Investor</td><td><strong>{{ $r['investor']['name'] }}</strong></td></tr>
                <tr><td class="k">Account number</td><td>{{ $r['investor']['account'] }}</td></tr>
                <tr><td class="k">Email</td><td>{{ $r['investor']['email'] }}</td></tr>
            </table>
        </td>
        <td style="width:45%; vertical-align:top;">
            <table class="kv">
                <tr><td class="k">Receipt number</td><td>{{ $number }}</td></tr>
                <tr><td class="k">Reference</td><td>{{ $r['reference'] }}</td></tr>
                @if ($r['trx_id'])
                    <tr><td class="k">Transaction ID</td><td>{{ $r['trx_id'] }}</td></tr>
                @endif
                <tr><td class="k">Date</td><td>{{ $r['date']->format('d F Y, H:i') }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<h2>Transaction</h2>
<table class="buckets">
    <tr>
        <td style="width:60%">
            <div class="label">{{ $r['is_marker'] ? 'Confirmation' : 'Amount' }}</div>
            <div class="value" style="font-size:19px;">
                {{ $r['is_marker'] ? '-' : Money::format($r['amount']) . ' ' . $c }}
            </div>
            <div class="muted" style="margin-top:3px;">{{ $r['description'] }}</div>
        </td>
        <td style="width:40%">
            <div class="label">Status</div>
            <div style="margin-top:4px;"><span class="stamp">{{ $r['status'] }}</span></div>
        </td>
    </tr>
</table>

@if ($r['lot'])
    <h2>Deal</h2>
    <table class="kv" style="width:70%">
        <tr><td class="k">Deal reference</td><td>{{ $r['lot']->lot_code }}</td></tr>
        <tr><td class="k">Deal name</td><td>{{ $r['lot']->project_name ?? '-' }}</td></tr>
        @if ($r['lot']->location)
            <tr><td class="k">Location</td><td>{{ $r['lot']->location }}</td></tr>
        @endif
        <tr><td class="k">Purchase date</td>
            <td>{{ $r['lot']->purchase_date ? $r['lot']->purchase_date->format('d M Y') : '-' }}</td></tr>
    </table>
@endif

<h2>Effect on your balances</h2>
<table class="grid">
    <thead>
    <tr>
        <th style="width:40%">Balance</th>
        <th class="num" style="width:20%">Before</th>
        <th class="num" style="width:20%">Movement</th>
        <th class="num" style="width:20%">After</th>
    </tr>
    </thead>
    <tbody>
    @foreach ([Bucket::AVAILABLE, Bucket::PROFIT, Bucket::COMMITTED] as $bucket)
        @php $delta = $r['after'][$bucket] - $r['before'][$bucket]; @endphp
        <tr>
            <td>{{ Bucket::label($bucket) }}</td>
            <td class="num">{{ Money::format($r['before'][$bucket]) }}</td>
            <td class="num">{{ abs($delta) < 0.000001 ? '-' : Money::signed($delta) }}</td>
            <td class="num">{{ Money::format($r['after'][$bucket]) }}</td>
        </tr>
    @endforeach
    <tr>
        <td><strong>Total Investor Position</strong></td>
        <td class="num"><strong>{{ Money::format($r['before']['total']) }}</strong></td>
        <td class="num"><strong>
            {{ abs($r['after']['total'] - $r['before']['total']) < 0.000001
                ? 'no change' : Money::signed($r['after']['total'] - $r['before']['total']) }}
        </strong></td>
        <td class="num"><strong>{{ Money::format($r['after']['total']) }}</strong></td>
    </tr>
    </tbody>
</table>

@if ($r['is_internal'])
    <p class="muted" style="margin-top:5px;">
        This movement transferred money between your own balances. Your Total Investor Position is unchanged
        by it: no money entered or left your account.
    </p>
@endif

@if ($r['explanation'])
    <div class="note">{{ $r['explanation'] }}</div>
@endif

<div class="note">
    This receipt confirms the recording of the above transaction in your investor account. Your position with
    the company is a USD account balance. It does not represent ownership of, or title to, any physical gold.
</div>

<div class="foot">
    {{ $branding['name'] }} - {{ $r['title'] }} {{ $number }} · {{ $r['investor']['account'] }} ·
    reference {{ $r['reference'] }} · issued {{ now()->format('d M Y H:i') }} ·
    amounts in {{ $c }}
</div>

</body></html>
