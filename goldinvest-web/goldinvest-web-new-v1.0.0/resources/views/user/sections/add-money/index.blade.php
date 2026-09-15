@extends('user.layouts.master')
@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
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
                            <h5 class="title">{{ __("Add Money Form") }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <form class="card-form" action="{{ setRoute("user.add.money.submit") }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12 form-group text-center">
                                        <div class="exchange-area">
                                            <code class="d-block text-center"><span>{{__("Exchange Rate")}}</span> <span class="exchange-rate-show">--</span> </code>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6 col-xl-12 col-lg-6 col-md-6 col-sm-6 form-group">
                                        <label>{{ __("Payment Gateway") }} <span class="text--base">*</span></label>
                                        <select class="form--control select2" name="gateway_currency">
                                            <option value="" selected disabled>{{ __("Select Gateway") }}</option>
                                           @foreach ($payment_gateways ?? [] as $gateway )
                                                @foreach ($gateway->currencies as $currency )
                                                    <option data-item="{{ $currency->getOnly(['currency_code','rate','min_limit','max_limit','percent_charge','fixed_charge','fiat'])->makeJson() }}" value="{{ $currency->alias }}">{{ $gateway->name."".$currency->currency_code }} @if ($gateway->isManual()) (Manual) @endif</option>
                                                @endforeach
                                           @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-6 col-xl-12 col-lg-6 col-md-6 col-sm-6 form-group">
                                        <label>{{ __("Amount") }} <span class="text--base">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" placeholder="{{ __("Enter Amount") }}..." name="amount" value="{{ old("amount") }}" required>
                                            <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                        </div>
                                        <code class="d-block mt-10 text--warning text-end">{{ __("Available Balance") }} {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                                    </div>
                                    <div class="col-xl-12 col-lg-12 form-group">
                                        <div class="note-area">
                                            <code class="d-block limit-show">--</code>
                                            <code class="d-block charge-show">--</code>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12">
                                        <button type="submit" class="btn--base w-100">{{ __("Add Money") }} <i class="fas fa-plus-circle ms-1"></i></button>
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
                                                <span>{{ __("Request Amount") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="enter-amount">--</span>
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
                                        <span class="fees">--</span>
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
                <h4 class="title">{{ __("Add Money Log") }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                    <a href="{{ setRoute('user.history.transaction','add-money-log') }}" class="btn--base">{{ __("View More") }}</a>
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

@endsection
@push('script')
    <script>
        let defaultCurrency = "{{ get_default_currency_code() }}";
        let precision = 4;

        $("select[name=gateway_currency]").change(function() {
            var selectedItem = $(this).find(":selected");
            var currency = JSON.parse(selectedItem.attr("data-item"));

            run(currency);
        });

        $(".submit-form").submit(function(e) {
            e.preventDefault();
            let selectedCurrency = $("select[name=gateway_currency]").find(":selected");
            let result = false;
            if(selectedCurrency.length > 0) {
                result = run(selectedCurrency.attr("data-item"));
            }

            if(result == true) {
                $(this).find("button[type=submit]").click();
                $(this).unbind('submit').submit();
            }
        });

        function run(currency) {

            if(currency == "" || currency == null) {
                return false;
            }

            if(typeof currency == "string") {
                try {
                    currency = JSON.parse(currency);
                } catch (error) {
                    throwMessage('error',['Unaccepted Data Format!']);
                    return false;
                }
            }

            if(!$.isNumeric(currency.min_limit) || !$.isNumeric(currency.max_limit) || !$.isNumeric(currency.rate) || !$.isNumeric(currency.percent_charge) || !$.isNumeric(currency.fixed_charge)) {
                throwMessage('error',['Unaccepted Data Format!']);
                return false;
            }

            let enterAmount = $("input[name=amount]").val();
            (enterAmount == null || enterAmount == "") ? enterAmount = 0 : enterAmount = parseFloat(enterAmount);

            // get limit
            let minLimit = parseFloat(currency.min_limit) / parseFloat(currency.rate);
            let maxLimit = parseFloat(currency.max_limit) / parseFloat(currency.rate);
            // console.log(minLimit,maxLimit);
            $(".limit-show").text(`• {{ __("Limit") }} ${parseFloat(minLimit).toFixed(precision)} ${defaultCurrency} - ${parseFloat(maxLimit).toFixed(precision)} ${defaultCurrency}`);

            // get charges
            let percentChargeCalc = ((parseFloat(enterAmount) / 100) * parseFloat(currency.percent_charge)) * parseFloat(currency.rate);

            let fixedChargeCalc = parseFloat(currency.fixed_charge);

            $(".charge-show").text(`• {{ __("Charge:") }} ${parseFloat(fixedChargeCalc).toFixed(precision)} ${currency.currency_code} + ${parseFloat(currency.percent_charge).toFixed(precision)}% `);

            let totalCharges = parseFloat(fixedChargeCalc) + parseFloat(percentChargeCalc);

            $(".exchange-rate-show").text(`• {{ __("Rate:") }} 1.0000 ${defaultCurrency} = ${parseFloat(currency.rate).toString()} ${currency.currency_code}`);

            // Preview Section
            $(".enter-amount").text(`${parseFloat(enterAmount).toFixed(precision)} ${defaultCurrency}`);
            $(".fees").text(`${parseFloat(totalCharges).toFixed(precision)} ${currency.currency_code}`);

            let payable = (parseFloat(enterAmount) * parseFloat(currency.rate)) + parseFloat(totalCharges);

            // $(".payable").text(`${parseFloat(payable).toFixed(precision)} ${currency.currency_code}`);
            $(".payable").text(`${(parseFloat(payable).toString())} ${currency.currency_code}`);

            $(".will-get").text(`${parseFloat(enterAmount).toFixed(precision)} ${defaultCurrency}`);

            $(".exchange-rate").text(`1.0000 ${defaultCurrency} = ${parseFloat(currency.rate).toString()} ${currency.currency_code}`);

            return true;
        }

        $("input[name=amount]").keyup(function() {
            let selectedCurrency = $("select[name=gateway_currency]").find(":selected");
            if(selectedCurrency.length > 0) {
                run(selectedCurrency.attr("data-item"));
            }
        });

    </script>
@endpush
