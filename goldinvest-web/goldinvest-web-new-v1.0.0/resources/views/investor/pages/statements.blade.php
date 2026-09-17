@extends('user.layouts.master')
@php use App\Investor\Support\Money; @endphp
@section('content')
<div class="dashboard-area mt-10">
    <div class="dashboard-header-wrapper">
        <h3 class="title">{{ $page_title }}</h3>
    </div>

    @if ($error)
        <div class="alert alert-warning mt-20">{{ $error }}</div>
    @else
        <x-investor-buckets />

        <div class="custom-card mt-10">
            <div class="card-body">
                <h5 class="card-title">{{ __("Generate a statement") }}</h5>
                <form action="{{ setRoute('user.statements.generate') }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">{{ __("Period") }}</label>
                        <select name="period" id="statement-period" class="form-control form--control">
                            <option value="current">{{ __("Everything to date") }}</option>
                            <option value="month">{{ __("This month") }}</option>
                            <option value="year">{{ __("This year") }}</option>
                            <option value="custom">{{ __("Custom range") }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 statement-custom" style="display:none;">
                        <label class="form-label">{{ __("From") }}</label>
                        <input type="date" name="from" class="form-control form--control">
                    </div>
                    <div class="col-md-3 statement-custom" style="display:none;">
                        <label class="form-label">{{ __("To") }}</label>
                        <input type="date" name="to" class="form-control form--control">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn--base w-100">{{ __("Download PDF") }}</button>
                    </div>
                </form>
                <p class="text-muted mt-2 mb-0" style="font-size:13px;">
                    {{ __("A statement covering today is marked interim, because the day is not finished.") }}
                </p>
            </div>
        </div>

        <div class="dashboard-header-wrapper mt-20">
            <h4 class="title">{{ __("Recent activity") }}</h4>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                <tr>
                    <th>{{ __("Date") }}</th>
                    <th>{{ __("Description") }}</th>
                    <th class="text-end">{{ __("Money In") }}</th>
                    <th class="text-end">{{ __("Money Out") }}</th>
                    <th class="text-end">{{ __("Available") }}</th>
                    <th class="text-end">{{ __("Profit") }}</th>
                    <th class="text-end">{{ __("Committed") }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse (array_reverse($statement['movements']) as $m)
                    <tr>
                        <td>{{ $m['date']->format('d M Y') }}</td>
                        <td>
                            {{ $m['description'] }}
                            @if ($m['internal'])
                                <br><small>{{ __("Internal transfer between your balances") }}</small>
                            @endif
                        </td>
                        <td class="text-end">{{ $m['money_in'] !== null ? Money::format($m['money_in']) : '' }}</td>
                        <td class="text-end">{{ $m['money_out'] !== null ? Money::format($m['money_out']) : '' }}</td>
                        <td class="text-end">{{ Money::format($m['balance_available']) }}</td>
                        <td class="text-end">{{ Money::format($m['balance_profit']) }}</td>
                        <td class="text-end">{{ Money::format($m['balance_committed']) }}</td>
                        <td class="text-end">
                            <a href="{{ setRoute('user.documents.receipt', $m['reference']) }}"
                               class="btn btn-sm btn--base">{{ __("Receipt") }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">{{ __("No activity yet.") }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <div class="dashboard-header-wrapper mt-20">
        <h4 class="title">{{ __("Issued statements") }}</h4>
    </div>
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
            <tr>
                <th>{{ __("Statement") }}</th>
                <th>{{ __("Period") }}</th>
                <th>{{ __("Issued") }}</th>
                <th class="text-end">{{ __("Total position") }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($documents as $document)
                <tr>
                    <td>{{ $document->document_number }}</td>
                    <td>
                        {{ $document->period_start?->format('d M Y') ?? __('Start') }}
                        -
                        {{ $document->period_end?->format('d M Y') ?? __('Now') }}
                        @if (data_get($document->meta, 'interim')) <span class="badge bg-warning">{{ __("Interim") }}</span> @endif
                    </td>
                    <td>{{ $document->generated_at->format('d M Y, H:i') }}</td>
                    <td class="text-end">
                        {{ Money::format($document->closing_available + $document->closing_profit + $document->closing_committed) }}
                    </td>
                    <td class="text-end">
                        <a href="{{ setRoute('user.documents.download', $document->id) }}"
                           class="btn btn-sm btn--base">{{ __("Download") }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">{{ __("No statements issued yet.") }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ get_paginate($documents) }}
</div>
@endsection

@push('script')
<script>
    (function () {
        var select = document.getElementById('statement-period');
        if (!select) return;
        function toggle() {
            var show = select.value === 'custom';
            document.querySelectorAll('.statement-custom').forEach(function (el) {
                el.style.display = show ? '' : 'none';
            });
        }
        select.addEventListener('change', toggle);
        toggle();
    })();
</script>
@endpush
