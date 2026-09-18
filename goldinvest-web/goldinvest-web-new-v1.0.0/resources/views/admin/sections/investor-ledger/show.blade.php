@extends('admin.layouts.master')
@php use App\Investor\Support\Money; @endphp
@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        ['name' => __("Dashboard"), 'url' => setRoute("admin.dashboard")],
        ['name' => __("Investor Ledgers"), 'url' => setRoute("admin.investor.ledger.index")],
    ], 'active' => $user->username])
@endsection
@section('content')
<div class="dashboard-area">
    <div class="dashboard-header-wrapper">
        <h4 class="title">{{ $user->username }} <small class="text-muted">{{ $user->email }}</small></h4>
    </div>

    <div class="alert {{ $report['passed'] ? 'alert-success' : 'alert-danger' }} mt-10">
        <strong>{{ $report['passed'] ? __('Reconciliation: PASS') : __('Reconciliation: FAIL') }}</strong>
        <ul class="mb-0 mt-1">
            @foreach ($report['checks'] as $name => $check)
                <li>[{{ $check['passed'] ? 'PASS' : 'FAIL' }}] {{ $name }} - {{ $check['message'] }}</li>
            @endforeach
        </ul>
        @if (! $report['passed'])
            <p class="mb-0 mt-2">{{ __("No statement can be issued for this investor until this is resolved.") }}</p>
        @endif
    </div>

    <div class="row mt-10">
        <div class="col-md-3"><div class="card"><div class="card-body">
            <span class="text-muted">{{ __("Available") }}</span>
            <h5>{{ Money::format($report['position']['available']) }}</h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">
            <span class="text-muted">{{ __("Profit") }}</span>
            <h5>{{ Money::format($report['position']['profit']) }}</h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">
            <span class="text-muted">{{ __("Committed") }}</span>
            <h5>{{ Money::format($report['position']['committed']) }}</h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">
            <span class="text-muted">{{ __("Total position") }}</span>
            <h5>{{ Money::format($report['total']) }}</h5></div></div></div>
    </div>

    <div class="card mt-20"><div class="card-body">
        <form method="POST" action="{{ setRoute('admin.investor.ledger.statement', $user->id) }}"
              class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">{{ __("From") }}</label>
                <input type="date" name="from" class="form--control">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __("To") }}</label>
                <input type="date" name="to" class="form--control">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn--base w-100">{{ __("Issue statement") }}</button>
            </div>
        </form>
        <form method="POST" action="{{ setRoute('admin.investor.ledger.receipts', $user->id) }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn--base">{{ __("Issue receipts for all movements") }}</button>
        </form>
    </div></div>

    <div class="dashboard-header-wrapper mt-20"><h5 class="title">{{ __("Ledger") }}</h5></div>
    <div class="table-responsive">
        <table class="custom-table">
            <thead><tr>
                <th>{{ __("Seq") }}</th><th>{{ __("Date") }}</th><th>{{ __("Event") }}</th>
                <th>{{ __("Bucket") }}</th><th class="text-end">{{ __("Amount") }}</th>
                <th class="text-end">{{ __("Available") }}</th><th class="text-end">{{ __("Profit") }}</th>
                <th class="text-end">{{ __("Committed") }}</th><th>{{ __("Reference") }}</th>
                <th>{{ __("Original trx") }}</th>
            </tr></thead>
            <tbody>
            @foreach ($entries as $e)
                <tr>
                    <td>{{ $e->seq }}</td>
                    <td>{{ $e->occurred_at->format('d M Y H:i') }}</td>
                    <td>{{ $e->event_type }}</td>
                    <td>{{ $e->bucket }}</td>
                    <td class="text-end">{{ Money::format($e->amount_usd) }}</td>
                    <td class="text-end">{{ Money::format($e->balance_available) }}</td>
                    <td class="text-end">{{ Money::format($e->balance_profit) }}</td>
                    <td class="text-end">{{ Money::format($e->balance_committed) }}</td>
                    <td>{{ $e->reference }}</td>
                    <td>{{ $e->trx_id ?? '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="dashboard-header-wrapper mt-20"><h5 class="title">{{ __("Documents") }}</h5></div>
    <div class="table-responsive">
        <table class="custom-table">
            <thead><tr>
                <th>{{ __("Number") }}</th><th>{{ __("Document") }}</th><th>{{ __("Reference") }}</th>
                <th>{{ __("Issued") }}</th><th>{{ __("SHA-256") }}</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($documents as $d)
                <tr @if ($d->revoked_at) class="text-muted" @endif>
                    <td>{{ $d->document_number }}</td>
                    <td>{{ $d->title }}@if ($d->revoked_at)<br><small>{{ __("superseded") }}</small>@endif</td>
                    <td>{{ $d->event_reference }}</td>
                    <td>{{ $d->generated_at->format('d M Y H:i') }}</td>
                    <td><small>{{ substr($d->file_hash, 0, 16) }}...</small></td>
                    <td class="text-end">
                        <a href="{{ setRoute('admin.investor.ledger.document.download', $d->id) }}"
                           class="btn btn-sm btn--base">{{ __("Download") }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">{{ __("No documents issued.") }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
