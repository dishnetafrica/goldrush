@extends('user.layouts.master')

@push('css')

@endpush

@section('content')
    <div class="dashboard-area mt-10">
        <div class="dashboard-header-wrapper">
            <h3 class="title">{{ $page_title }}</h3>
        </div>
    </div>
    <div class="row mb-30-none">
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __("Pay With This Address") }} ({{ $transaction->gateway_currency->currency_code }})</h5>
                    </div>
                    <div class="dash-payment-body">
                        @if ($transaction->status == payment_gateway_const()::STATUSWAITING)
                        <form class="card-form" method="POST" action="{{ setRoute('user.add.money.payment.crypto.confirm',$transaction->trx_id) }}">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <div class="input-group">
                                        <input type="text" value="{{ $transaction->details->payment_info->receiver_address ?? "" }}" class="form--control ref-input text-light copiable" readonly>
                                        <div class="input-group-text copytext copy-button"><i class="las la-copy"></i></div>
                                    </div>
                                </div>
                                <div class="col-lg-12 form-group">
                                    <div class="thumb-area d-flex justify-content-center">
                                        <img src="{{ $transaction->details->payment_info->receiver_qr_image ?? "" }}" alt="Qr Code">
                                    </div>
                                </div>
                                {{-- Print Dynamic Input Filed if Have START --}}
                                @foreach ($transaction->details->payment_info->requirements ?? [] as $input)
                                <div class="col-lg-12 form-group">
                                    <label for="">{{ $input->label }} </label>
                                    <input type="text" name="{{ $input->name }}" placeholder="{{ $input->placeholder ?? "" }}" class="form--control" @if ($input->required)
                                        @required(true)
                                    @endif>
                                </div>
                                @endforeach
                            {{-- Print Dynamic Input Filed if Have END --}}
                                <div class="col-lg-12">
                                    <button type="submit" class="btn--base w-100">{{ __("Proceed") }}</button>
                                </div>
                            </div>
                        </form>
                        @else
                            <div class="payment-received-alert">
                                <div class="text-center text--success">
                                    {{ __("Payment Received Successfully!") }}
                                </div>

                                <div class="txn-hash text-center mt-2 text--info">
                                    <strong>{{ __("Txn Hash:") }} </strong>
                                    <span>{{ $transaction->details->payment_info->txn_hash ?? "" }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __("Preview") }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-receipt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Request Amount") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="enter-amount">{{ get_amount($transaction->request_amount, $transaction->creator_wallet->currency->code) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-exchange-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Exchange Rate") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="exchange-rate">1 {{ $transaction->creator_wallet->currency->code }} =
                                        {{ get_amount($transaction->exchange_rate, $transaction->gateway_currency->currency_code,8) }}
                                    </span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-hand-holding-usd"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Fees") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="fees">{{ get_amount($transaction->total_charge, $transaction->creator_wallet->currency->code,4) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Total Payable") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="payable">{{ get_amount($transaction->total_payable, $transaction->gateway_currency->currency_code,8) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="lab la-get-pocket"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Will Get") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--base will-get">{{ get_amount($transaction->receive_amount, $transaction->creator_wallet->currency->code,4) }}</span>
                                </div>
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
    $('.copytext').on('click',function(){
       var copyText = document.getElementById("referralURL");
       copyText.select();
       copyText.setSelectionRange(0, 99999);
       document.execCommand("copy");

       throwMessage('success',["Copied: " + copyText.value]);
   });
</script>
@endpush
