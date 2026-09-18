@extends('user.layouts.master')
@php use App\Investor\Support\Money; @endphp
@section('content')
<div class="dashboard-area mt-10">
    <div class="dashboard-header-wrapper">
        <h3 class="title">{{ $goldLot->lot_code }} - {{ $goldLot->project_name }}</h3>
    </div>

    <div class="row mt-10">
        <div class="col-lg-6">
            <div class="custom-card">
                <div class="card-body">
                    <h5 class="card-title">{{ __("Your financial result") }}</h5>
                    <table class="custom-table mb-0">
                        <tr><td>{{ __("Capital you committed") }}</td>
                            <td class="text-end">{{ Money::format($allocation->amount_usd) }}</td></tr>
                        <tr><td>{{ __("Your agreed profit share") }}</td>
                            <td class="text-end">
                                {{ $distribution ? number_format($distribution->profit_share_percent, 0) . '%' : '-' }}
                            </td></tr>
                        <tr><td>{{ __("Profit credited to you") }}</td>
                            <td class="text-end">
                                {{ $distribution ? Money::format($distribution->amount_usd) : '-' }}
                            </td></tr>
                        <tr><td>{{ __("Capital returned") }}</td>
                            <td class="text-end">
                                {{ $allocation->status === 'returned' ? Money::format($allocation->amount_usd) : __('Still committed') }}
                            </td></tr>
                        <tr class="fw-bold"><td>{{ __("Total credited to your account") }}</td>
                            <td class="text-end">
                                {{ Money::format(($allocation->status === 'returned' ? $allocation->amount_usd : 0) + ($distribution->amount_usd ?? 0)) }}
                            </td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="custom-card">
                <div class="card-body">
                    <h5 class="card-title">{{ __("The company's recorded trading result") }}</h5>
                    <p class="mb-2" style="font-size:13px; opacity:.8;">
                        <span class="badge {{ $goldLot->expensesFinalised() ? 'badge--success' : 'badge--warning' }}">
                            {{ $goldLot->expenseStatusLabel() }}
                        </span>
                    </p>
                    <table class="custom-table mb-0">
                        <tr><td>{{ __("Purchase date") }}</td>
                            <td class="text-end">{{ $goldLot->purchase_date?->format('d M Y') }}</td></tr>
                        <tr><td>{{ __("Gold purchased") }}</td>
                            <td class="text-end">{{ number_format($result['gross_grams'], 2) }} g</td></tr>
                        <tr><td>{{ __("Lost in processing") }}</td>
                            <td class="text-end">{{ number_format($result['waste_grams'] ?? 0, 2) }} g</td></tr>
                        <tr><td>{{ __("Gold sold") }}</td>
                            <td class="text-end">{{ number_format($result['sold_grams'], 2) }} g</td></tr>
                        <tr><td>{{ __("Sale proceeds") }}</td>
                            <td class="text-end">{{ Money::format($result['proceeds_usd']) }}</td></tr>
                        <tr><td>{{ __("Cost of the gold sold") }}</td>
                            <td class="text-end">{{ Money::format($result['cost_of_goods_sold_usd']) }}</td></tr>
                        <tr><td>{{ __("Deal expenses") }}</td>
                            <td class="text-end">{{ Money::format($result['expenses_usd']) }}</td></tr>
                        <tr class="fw-bold"><td>{{ __("Net trading profit (recorded to date)") }}</td>
                            <td class="text-end">{{ Money::format($result['net_profit_usd']) }}</td></tr>
                    </table>
                    @unless ($goldLot->expensesFinalised())
                        <p class="mt-2 mb-0" style="font-size:13px; opacity:.85;">
                            <strong>{{ __("Expense capture") }}:</strong>
                            {{ __("Expenses are recorded as they are incurred. This figure reflects expenses recorded to date and may change when additional deal expenses are recorded.") }}
                        </p>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    @if ($movements->isNotEmpty())
        <div class="dashboard-header-wrapper mt-20">
            <h4 class="title">{{ __("Your account movements for this deal") }}</h4>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                <tr>
                    <th>{{ __("Date") }}</th>
                    <th>{{ __("Movement") }}</th>
                    <th>{{ __("Reference") }}</th>
                    <th class="text-end">{{ __("Amount") }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($movements as $legs)
                    @php $first = $legs->first(); @endphp
                    <tr>
                        <td>{{ $first->occurred_at->format('d M Y') }}</td>
                        <td>{{ $first->description }}</td>
                        <td>{{ $first->reference }}</td>
                        <td class="text-end">
                            {{ Money::format($legs->where('amount_usd', '>', 0)->sum('amount_usd')) }}
                        </td>
                        <td class="text-end">
                            <a href="{{ setRoute('user.documents.receipt', $first->reference) }}"
                               class="btn btn-sm btn--base">{{ __("Receipt") }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-info mt-20">
            {{ __("This deal predates your account ledger, so it has no account movements recorded against it. It is shown as a historical attribution record.") }}
        </div>
    @endif

    <div class="alert alert-secondary mt-20" style="font-size:13px;">
        {{ __("Gold quantities describe what the company bought and sold. They are shown so you can see how the trading result arose, and do not represent gold held for you or in your name.") }}
    </div>
</div>
@endsection
