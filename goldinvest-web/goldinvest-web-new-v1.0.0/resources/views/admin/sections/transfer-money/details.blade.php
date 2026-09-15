@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('Transaction Details'),
    ])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form">
                <div class="row align-items-center mb-10-none">
                    <div class="col-xl-4 col-lg-4 form-group">
                        <ul class="user-profile-list-two">
                            <li class="one">{{ __("Date:") }} <span>{{ $transaction->created_at ? $transaction->created_at->format("Y-m-d h:i A") : 'N/A' }}</span></li>
                            <li class="two">{{ __("Transaction ID:") }} <span>{{ $transaction->trx_id ?? 'N/A' }}</span></li>
                            <li class="three">{{ __("Sender Mail:") }} <span>{{ $transaction->creator->email ?? 'N/A' }}</span></li>
                            <li class="four">{{ __("Receiver Mail:") }} <span>{{ $transaction->receiver_info->email ?? 'N/A' }}</span></li>
                            <li class="five">{{ __("Request Amount:") }} <span>{{ $transaction->request_amount && $transaction->creator_wallet->currency ? get_amount($transaction->request_amount, $transaction->creator_wallet->currency->code) : 'N/A' }}</span></li>
                        </ul>
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        <div class="user-profile-thumb">
                            <img src="{{ get_image($item->user->image ?? "","user-profile") }}" alt="payment">
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        <ul class="user-profile-list two">
                            <li class="one">{{ __("Charge:") }} <span>{{ $transaction->total_charge && $transaction->request_currency ? get_amount($transaction->total_charge, $transaction->request_currency) : 'N/A' }}</span></li>
                            <li class="two">{{ __("After Charge:") }} <span>{{ $transaction->request_amount && $transaction->total_charge && $transaction->request_currency ? get_amount(($transaction->request_amount + $transaction->total_charge), $transaction->request_currency) : 'N/A' }}</span></li>
                            <li class="three">{{ __("Rate:") }} <span>{{ $transaction->request_currency && $transaction->exchange_rate && $transaction->payment_currency ? '1 ' . $transaction->request_currency . ' = ' . get_amount($transaction->exchange_rate, $transaction->payment_currency, 'double') : 'N/A' }}</span></li>
                            <li class="four">{{ __("Receiver Will Get:") }} <span>{{ $transaction->receive_amount && $transaction->payment_currency ? get_amount($transaction->receive_amount, $transaction->payment_currency, 'double') : 'N/A' }}</span></li>
                            <li class="five">{{ __("Status:") }} <span class="{{ $transaction->StringStatus->class ?? '' }}">{{ __($transaction->StringStatus->value) ?? 'N/A' }}</span></li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')

@endpush
