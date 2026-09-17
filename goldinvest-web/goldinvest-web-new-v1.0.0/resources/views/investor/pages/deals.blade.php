@extends('user.layouts.master')
@php use App\Investor\Support\Money; @endphp
@section('content')
<div class="dashboard-area mt-10">
    <div class="dashboard-header-wrapper">
        <h3 class="title">{{ $page_title }}</h3>
    </div>
    <p class="text-muted">
        {{ __("Trading deals your capital has been attributed to. The company buys and sells the gold; your position is a USD balance with the company.") }}
        <br>
        {{ __("Deal results shown are recorded results. Expenses are recorded as they are incurred, so a deal marked as having expenses pending may still change.") }}
    </p>
    <div class="table-responsive mt-10">
        <table class="custom-table">
            <thead>
            <tr>
                <th>{{ __("Deal") }}</th>
                <th>{{ __("Name") }}</th>
                <th>{{ __("Date") }}</th>
                <th class="text-end">{{ __("Capital") }}</th>
                <th class="text-end">{{ __("Your share") }}</th>
                <th class="text-end">{{ __("Your profit") }}</th>
                <th>{{ __("Status") }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($deals as $deal)
                <tr>
                    <td>{{ $deal['reference'] }}</td>
                    <td>{{ $deal['name'] }}</td>
                    <td>{{ $deal['date'] ? \Illuminate\Support\Carbon::parse($deal['date'])->format('d M Y') : '-' }}</td>
                    <td class="text-end">{{ Money::format($deal['capital']) }}</td>
                    <td class="text-end">{{ $deal['investor_share'] !== null ? number_format($deal['investor_share'], 0) . '%' : '-' }}</td>
                    <td class="text-end">{{ $deal['investor_profit'] !== null ? Money::format($deal['investor_profit']) : '-' }}</td>
                    <td>
                        {{ $deal['status'] }}
                        @if (! $deal['ledger_backed'])
                            <br><small>{{ __("Predates the account ledger") }}</small>
                        @endif
                        @if ($deal['expense_status'])
                            <br><small>{{ $deal['expense_status'] }}</small>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ setRoute('user.deals.show', $deal['reference']) }}"
                           class="btn btn-sm btn--base">{{ __("View") }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">{{ __("Your capital has not been attributed to a deal yet.") }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
