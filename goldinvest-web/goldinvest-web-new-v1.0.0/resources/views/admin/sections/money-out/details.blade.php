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
                            <li class="three">{{ __("Username:") }} <span>{{ $transaction->creator->username ?? 'N/A' }}</span></li>
                            <li class="four">{{ __("Method:") }} <span>{{ $transaction->gateway_currency->gateway->name ?? 'N/A' }}</span></li>
                            <li class="five">{{ __("Amount:") }} <span>{{ $transaction->request_amount && $transaction->creator_wallet ? get_amount($transaction->request_amount, $transaction->creator_wallet->currency->code) : 'N/A' }}</span></li>
                        </ul>
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        <div class="user-profile-thumb">
                            <img src="{{ get_image($transaction->gateway_currency->gateway->image ?? '','payment-gateways') }}" alt="payment">
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
    @if ($transaction->gateway_currency->gateway->isManual())
        <div class="custom-card mt-15">
            <div class="card-header">
                <h6 class="title">{{ __("Information of Logs") }}</h6>
            </div>
            <div class="card-body">
                @if ($transaction->details != null && isset($transaction->details->input_values))
                <ul class="product-sales-info">
                    @php
                        $transaction_datas = $transaction->details->input_values  ?? [];
                    @endphp
                    @foreach ($transaction_datas as $item)
                        @if ($item->type == "file")
                            @php
                                $file_link = get_file_link("transaction",$item->value);
                            @endphp
                            <li>
                                <span class="kyc-title">{{ $item->label }}:</span>
                                @if ($file_link == false)
                                    <span>{{ __("File not found!") }}</span>
                                    @continue
                                @endif

                                @if (its_image($item->value))
                                    <span class="product-sales-thumb">
                                        <a class="img-popup" data-rel="lightcase:myCollection" href="{{ $file_link }}">
                                            <img src="{{ $file_link }}" alt="{{ $item->label }}">
                                        </a>
                                    </span>
                                @else
                                    <span class="text--danger">
                                        @php
                                            $file_info = get_file_basename_ext_from_link($file_link);
                                        @endphp
                                        <a href="{{ setRoute('file.download',["kyc-files",$item->value]) }}" >
                                            {{ Str::substr($file_info->base_name ?? "", 0 , 20 ) ."..." . $file_info->extension ?? "" }}
                                        </a>
                                    </span>
                                @endif
                            </li>
                        @else
                            <li>
                                <span class="kyc-title">{{ $item->label }}:</span>
                                <span>{{ $item->value }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <div class="product-sales-btn">
                    @if ($transaction->status == payment_gateway_const()::STATUSPENDING)
                        @include('admin.components.button.custom',[
                            'type'          => "button",
                            'class'         => "approve-btn w-100",
                            'text'          => __("Approve"),
                            'permission'    => "admin.money.out.approve",
                        ])

                        @include('admin.components.button.custom',[
                            'type'          => "button",
                            'class'         => "bg--danger reject-btn w-100",
                            'text'          => __("Reject"),
                            'permission'    => "admin.money.out.reject",
                        ])
                    @endif
                </div>
            @else
                <div class="alert alert-primary">{{ __("Transaction information not submitted yet") }}</div>
            @endif
            </div>
        </div>

        {{-- Reject Modal --}}
        @include('admin.components.modals.money-out-reject',compact("transaction"))
    @endif
@endsection

@push('script')
    <script>
        openModalWhenError("reject-modal","#reject-modal");
        $(".approve-btn").click(function(){
            var actionRoute = "{{ setRoute('admin.money.out.approve') }}";
            var target      = "{{ $transaction->trx_id }}";
            console.log(target);
            var message     = `{{ __('Are you sure to approve this') }} ({{ $transaction->trx_id }}) {{ __('transaction.') }}`;
            openDeleteModal(actionRoute,target,message,"{{ __('Approve') }}","POST");
        });
        function openDeleteModal(URL,target,message,actionBtnText = "{{ __('Remove') }}",method = "DELETE"){
            if(URL == "" || target == "") {
                return false;
            }

            if(message == "") {
                message = "{{ __('Are you sure to delete ?') }}";
            }
            var method = `<input type="hidden" name="_method" value="${method}">`;
            openModalByContent(
                {
                    content: `<div class="card modal-alert border-0">
                                <div class="card-body">
                                    <form method="POST" action="${URL}">
                                        <input type="hidden" name="_token" value="${laravelCsrf()}">
                                        ${method}
                                        <div class="head mb-3">
                                            ${message}
                                            <input type="hidden" name="target" value="${target}">
                                        </div>
                                        <div class="foot d-flex align-items-center justify-content-between">
                                            <button type="button" class="modal-close btn btn--info">{{ __("Close") }}</button>
                                            <button type="submit" class="alert-submit-btn btn btn--danger btn-loading">${actionBtnText}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>`,
                },

            );
            }
    </script>
@endpush
