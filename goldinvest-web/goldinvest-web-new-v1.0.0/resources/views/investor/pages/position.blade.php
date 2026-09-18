@extends('user.layouts.master')
@php use App\Investor\Support\Money; @endphp
@section('content')
<div class="dashboard-area mt-10">
    <div class="dashboard-header-wrapper"><h3 class="title">{{ $page_title }}</h3></div>
    @if ($p['interim'])
        <div class="alert alert-warning mt-10">{{ __("Interim: this view includes today, so figures may still change. A closed-period statement is final.") }}</div>
    @endif
    {{-- The vendor's own dashboard tiles, so the figures are legible in the user theme. --}}
    <div class="dashboard-item-area mt-10">
        <div class="row mb-20-none">
            @foreach ([
                ['label' => __('Available balance'), 'value' => $p['closing']['available'], 'icon' => 'fas fa-wallet'],
                ['label' => __('Profit balance'), 'value' => $p['closing']['profit'], 'icon' => 'fas fa-chart-line'],
                ['label' => __('Capital in active deals'), 'value' => $p['closing']['committed'], 'icon' => 'fas fa-coins'],
                ['label' => __('Total position'), 'value' => $p['closing']['total'], 'icon' => 'fas fa-university'],
            ] as $card)
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ $card['label'] }}</span>
                            <h3 class="title">{{ Money::format($card['value']) }} <span class="text--base">{{ $p['currency'] }}</span></h3>
                        </div>
                        <div class="dashboard-icon"><i class="{{ $card['icon'] }}"></i></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="custom-card mt-10"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">{{ __("From") }}</label><input type="date" name="from" value="{{ $input['from'] ?? '' }}" class="form-control form--control"></div>
            <div class="col-md-3"><label class="form-label">{{ __("To") }}</label><input type="date" name="to" value="{{ $input['to'] ?? '' }}" class="form-control form--control"></div>
            <div class="col-md-3"><button class="btn btn--base w-100" type="submit">{{ __("Show") }}</button></div>
            <div class="col-md-3"><a class="btn btn--secondary w-100" href="{{ setRoute('user.statements.index') }}">{{ __("Statements") }}</a></div>
        </form>
        <p class="text-muted mt-2 mb-0" style="font-size:13px;">{{ __("Opening position") }}: {{ Money::format($p['opening']['total']) }} ({{ $p['opening']['note'] }})</p>
    </div></div>
    <div class="dashboard-header-wrapper mt-20"><h4 class="title">{{ __("Movements") }}</h4></div>
    <div class="table-responsive"><table class="custom-table">
        <thead><tr><th>{{ __("Type") }}</th><th class="text-end">{{ __("Count") }}</th><th class="text-end">{{ __("Amount") }}</th></tr></thead>
        <tbody>@forelse ($p['movements'] as $m)<tr><td>{{ $m['label'] }}</td><td class="text-end">{{ $m['count'] }}</td><td class="text-end">{{ Money::signed($m['amount_usd']) }}</td></tr>
        @empty<tr><td colspan="3" class="text-center">{{ __("No movements in this period.") }}</td></tr>@endforelse</tbody></table></div>
    <div class="dashboard-header-wrapper mt-20"><h4 class="title">{{ __("Profit distributions received") }}</h4></div>
    <div class="table-responsive"><table class="custom-table">
        <thead><tr><th>{{ __("Reference") }}</th><th>{{ __("Trading period") }}</th><th>{{ __("Declared") }}</th><th class="text-end">{{ __("Amount") }}</th><th>{{ __("Status") }}</th></tr></thead>
        <tbody>@forelse ($p['distributions'] as $d)<tr><td>{{ $d['reference'] }}<br><small>{{ $d['ledger_reference'] }}</small></td><td>{{ $d['period'] }}</td><td>{{ $d['declared_on'] }}</td>
            <td class="text-end">{{ Money::format($d['amount_usd']) }}</td><td>{{ $d['status'] }}</td></tr>
        @empty<tr><td colspan="5" class="text-center">{{ __("No distribution in this period.") }}</td></tr>@endforelse</tbody></table></div>
    <div class="dashboard-header-wrapper mt-20"><h4 class="title">{{ __("Withdrawals") }}</h4></div>
    <div class="table-responsive"><table class="custom-table">
        <thead><tr><th>{{ __("Date") }}</th><th>{{ __("Event") }}</th><th class="text-end">{{ __("Amount") }}</th><th>{{ __("Reference") }}</th></tr></thead>
        <tbody>@forelse ($p['withdrawals'] as $w)<tr><td>{{ $w['date'] }}</td><td>{{ $w['label'] }}</td><td class="text-end">{{ Money::format($w['amount_usd']) }}</td><td>{{ $w['reference'] }}</td></tr>
        @empty<tr><td colspan="4" class="text-center">{{ __("No withdrawals in this period.") }}</td></tr>@endforelse</tbody></table></div>
    <div class="dashboard-header-wrapper mt-20"><h4 class="title">{{ __("Statements") }}</h4></div>
    <div class="table-responsive"><table class="custom-table">
        <thead><tr><th>{{ __("Number") }}</th><th>{{ __("Period") }}</th><th>{{ __("Generated") }}</th><th></th></tr></thead>
        <tbody>@forelse ($p['statements'] as $s)<tr><td>{{ $s['number'] }}{{ $s['interim'] ? ' (interim)' : '' }}</td><td>{{ $s['period_start'] ?? 'start' }} - {{ $s['period_end'] ?? 'open' }}</td><td>{{ $s['generated_at'] }}</td>
            <td class="text-end"><a class="btn btn-sm btn--base" href="{{ setRoute('user.documents.download', $s['id']) }}">{{ __("Download") }}</a></td></tr>
        @empty<tr><td colspan="4" class="text-center">{{ __("No statement issued yet.") }}</td></tr>@endforelse</tbody></table></div>
</div>
@endsection
