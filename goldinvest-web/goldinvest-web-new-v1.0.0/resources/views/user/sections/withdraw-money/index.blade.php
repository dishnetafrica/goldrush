@extends('user.layouts.master')
@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@if ($money_out_settings?->c_balance == false && $money_out_settings?->p_balance == false)
 <div class="form-error text-warning mb-4 text-center"> {{ __("Withdraw service is temporary unavailable. If you have any queries, feel free to contact support. Thanks") }}
 </div>
@else
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
                            <h5 class="title">{{ __("Money Out Form") }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <form class="card-form"  action="{{ setRoute("user.withdraw.money.submit") }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12 form-group text-center">
                                        <div class="exchange-area">
                                            <code class="d-block text-center">
                                                <span>{{ __("Available Balance") }}</span>
                                                <span id="available-balance">{{ authWalletBalance() }}</span>
                                                <span>{{ get_default_currency_code() }}</span>
                                            </code>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12 form-group">
                                        <label>{{ __("Balance Type") }} <span class="text--base">*</span></label>
                                        @php
                                            $old_wallet_type = old('wallet_type');
                                        @endphp
                                        <select class="form--control  select2" name="wallet_type">
                                            <option selected disabled>{{ __("Choose One") }}</option>

                                            @if ($money_out_settings?->c_balance == true)
                                                <option value="c_balance" @if ($old_wallet_type == 'c_balance') @selected(true) @endif>{{ __("Wallet Balance") }}</option>
                                            @endif
                                            @if ($money_out_settings?->p_balance == true)
                                                <option value="p_balance" @if ($old_wallet_type == 'p_balance') @selected(true) @endif>{{ __("Profit Balance") }}</option>
                                            @endif
                                    </select>
                                    </div>
                                    <div class="col-xxl-6 col-xl-12 col-lg-6 col-md-6 col-sm-6 form-group">
                                        <label>{{ __("Payment Gateway ") }}<span class="text--base">*</span></label>
                                        @php
                                            $old_payment_gateway = old('payment_gateway');
                                        @endphp
                                            <select class="form--control select2" name="payment_gateway">
                                                <option selected disabled>{{ __("Select Gateway") }}</option>
                                                @foreach ($payment_gateways as $item)
                                                    <option value="{{ $item->alias }}" data-item="{{ json_encode($item->currencies()->select(['name','rate','currency_code','percent_charge','fixed_charge','min_limit','max_limit'])->first()) }}" @if ($old_payment_gateway == $item->alias) @selected(true) @endif>{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                    </div>
                                    <div class="col-xxl-6 col-xl-12 col-lg-6 col-md-6 col-sm-6 form-group">
                                        <label>{{ __("Amount") }} <span class="text--base">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" placeholder="{{ __("Enter Amount") }}..." name="amount" value="{{ old("amount") }}" required>
                                            <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12 form-group">
                                        <div class="note-area">
                                            <code class="d-block limit-show">--</code>
                                            <code class="d-block charge-show">--</code>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12">
                                        <button type="submit" class="btn--base w-100">{{ __("Proceed") }} <i class="fas fa-plus-circle ms-1"></i></button>
                                    </div>
                                </div>
                            </form>
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
                                                <span>{{ __("Withdraw Amount") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="withdraw-amount">--</span>
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
                                        <span class="exchange-rate">--</span>
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
                                        <span class="total-charges">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-money-check-alt"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Total Payable Amount") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="payable">--</span>
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
                                        <span class="text--base will-get">--</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-list-area mt-30">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __("Money Out Log") }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <a href="{{ setRoute('user.history.transaction','money-out-log') }}" class="btn--base">{{ __("View More") }}</a>
                    </div>
                </div>
            </div>
                @include('user.components.transaction.log',[
                    'logs' => $transactions
                ])
        </div>


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@endif
@endsection
@push('script')
    <script>

        let default_currency_code = "{{ get_default_currency_code() }}";
        let precision = 4;

        $("select[name=payment_gateway]").change(function() {
            run();
        });

        $("input[name=amount]").keyup(function() {
            run();
        });

        function run() {
            let paymentGatewaySelect = $("select[name=payment_gateway]");
            let gatewaySelectedValue = paymentGatewaySelect.val();

            if(gatewaySelectedValue == null || gatewaySelectedValue == "") return false;

            let amount = $("input[name=amount]").val();

            let gatewayCurrency = JSON.parse(paymentGatewaySelect.find(":selected").attr("data-item"));

            (amount == null || amount == "" || !$.isNumeric(amount)) ? amount = 0 : amount = amount;

            $(".withdraw-amount").text(`${amount} ${default_currency_code}`);

            let fixedCharge         = gatewayCurrency.fixed_charge ?? 0;
            let percentCharge       = gatewayCurrency.percent_charge ?? 0;
            let minLimit            = gatewayCurrency.min_limit ?? 0;
            let maxLimit            = gatewayCurrency.max_limit ?? 0;
            let rate                = gatewayCurrency.rate ?? 1;
            let gatewayCurrencyCode = gatewayCurrency.currency_code ?? "-";
            let fixedChargeCalc = parseFloat(fixedCharge) / rate; // default currency fixed charge
            let percentChargeCalc = (parseFloat(amount) / 100) * parseFloat(percentCharge);
            let minLimitCalc = minLimit / parseFloat(rate);
            let maxLimitCalc = maxLimit / parseFloat(rate);
            $(".limit-show").text(`• {{ __("Limit") }} ${parseFloat(minLimitCalc).toFixed(precision)} ${default_currency_code} - ${parseFloat(maxLimitCalc).toFixed(precision)} ${default_currency_code}`);
            $(".charge-show").text(`• {{ __("Charge:") }} ${parseFloat(fixedChargeCalc).toFixed(precision)} ${default_currency_code} + ${parseFloat(percentCharge).toFixed(precision)}% `);

            $(".exchange-rate").text(`1 ${default_currency_code} = ${parseFloat(rate).toFixed(precision)} ${gatewayCurrencyCode}`);


            let totalCharge = parseFloat(fixedChargeCalc) + parseFloat(percentChargeCalc) // total charge in default currency
            $(".total-charges").text(`${parseFloat(totalCharge).toFixed(precision)} ${default_currency_code}`);

            let willGet = parseFloat(amount) * parseFloat(rate); // get amount with gateway currency

            $('.will-get').text(`${willGet.toFixed(precision)} ${gatewayCurrencyCode}`);

            let totalPayable = parseFloat(amount) + parseFloat(totalCharge);
            $(".payable").text(`${totalPayable.toFixed(precision)} ${default_currency_code}`);

        }

        $("select[name=wallet_type]").change(function() {
        updateAvailableBalance();
        });

        function updateAvailableBalance() {
            let walletType = $("select[name=wallet_type]").val();
            $.ajax({
                url: "{{ route('user.get.wallet.balance') }}",
                type: "GET",
                data: { wallet_type: walletType },
                success: function(response) {
                    $("#available-balance").text(response.balance);
                }
            });
        }
    </script>
@endpush
