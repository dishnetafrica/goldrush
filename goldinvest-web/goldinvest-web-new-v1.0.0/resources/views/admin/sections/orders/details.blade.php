@extends('admin.layouts.master')

@push('css')

    <style>
        .fileholder {
            min-height: 374px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 330px !important;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ],
    ], 'active' => __("Order Details")])
@endsection
@section('content')
<div class="custom-card">
    <div class="card-header">
        <h6 class="title">{{ __($page_title) }}</h6>
    </div>
     <div class="card-body">
        <div class="row mb-30-none">
            <div class="col-lg-4 mb-30">
                <div class="booking-area">
                    <h4 class="title"><i class="fas fa-user text--base me-2"></i>{{ __("Gold Information") }}</h4>
                    <div class="thumb">
                        <img src="{{ get_image($order->gold->image ?? '','site-section') ?? '' }}" alt="profile">
                    </div>
                    <div class="content">
                        <div class="list-wrapper">
                            <ul class="list">
                                <li>{{ __("Title") }}:<span> {{ $order->gold->title->language->$lang->title ?? $order->gold->title->language->$default->title ?? "" }}</span></li>
                                <li>{{ __("Manufacturer") }}:<span> {{ $order->gold->manufacturer ?? "" }}</span></li>
                                <li>{{ __("Country of Origin") }}:<span> {{ $order->gold->country_of_origin ?? "" }}</span></li>
                                <li>{{ __("Price") }}:<span> {{ get_amount($order->gold->price) ?? "" }} {{ $default_currency->code }}</span></li>
                                <li>{{ __("Charge") }}:<span> {{ get_amount($order->gold->charge) ?? "" }} {{ $default_currency->code }}</span></li>
                                <li>{{ __("Weight") }}:<span> {{ $order->gold->weight ?? "" }}</span></li>
                                <li>{{ __("Purity") }}:<span> {{ $order->gold->purity ?? "" }}</span></li>
                                <li>{{ __("Type") }}:<span>{{ $order->gold->type ?? "" }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-30">
                <div class="booking-area">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="title mb-0"><i class="fas fa-user text--base me-2"></i>{{ __("Order Information") }}</h4>
                    </div>

                    <div class="content pt-0">
                        <div class="list-wrapper">
                            <ul class="list">
                                <li>{{ __("Quantity") }}:<span>{{ $order->quantity ?? "" }}</span></li>
                                <li>{{ __("Total Amount") }}:<span>{{ get_amount($order->total_amount) ?? "" }}</span></li>
                                <li>{{ __("Payment Type") }}:<span>
                                    @if($order->payment_type == global_const()::PAYMENT_TYPE_USER_WALLET) {{ __("User Wallet") }}
                                    @else {{ __("Cash on Delivery") }}
                                    @endif</span></li>
                                <li>{{ __("Full Mobile") }}:<span>{{ $order->full_mobile ?? "" }}</span></li>
                                <li>{{ __("Country") }}:<span>{{ $order->address->country ?? "" }}</span></li>
                                <li>{{ __("State") }}:<span>{{ $order->address->state ?? "" }}</span></li>
                                <li>{{ __("City") }}:<span>{{ $order->address->city ?? "" }}</span></li>
                                <li>{{ __("Zip Code") }}:<span>{{ $order->address->zip ?? "" }}</span></li>
                                <li>{{ __("Address") }}:<span>{{ $order->address->address ?? "" }}</span></li>
                                <li>{{ __("Order Status") }}:<span>
                                    @if($order->order_status == 1) {{ __("Accepted") }}
                                    @elseif($order->order_status == 2) {{ __("Ongoing") }}
                                    @elseif($order->order_status == 3) {{ __("Delivered") }}
                                    @elseif($order->order_status == 4) {{ __("Cancelled") }}
                                    @else {{ __("N/A") }}
                                    @endif
                                </span></li>
                                <li>{{ __("Full Name") }}:<span>{{ $order->user->firstname ?? "" }} {{ $order->user->lastname ?? "" }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
@push('script')
    <script>

    </script>
@endpush
