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
                            <h5 class="title">{{ __("Send Money Form") }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <form class="card-form" action="{{ setRoute("user.transfer.money.confirmed") }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12 form-group text-center">
                                        <div class="exchange-area">
                                            <code class="d-block text-center"><span>{{ __("Exchange Rate") }}</span> 1 {{ $default_currency->code }} = {{ $exchange_rate }} {{ $default_currency->code }}</code>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Amount") }} <span class="text--base">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form--control sender-amount number-input" placeholder="{{ __("Enter Amount") }}..." name="sender_amount" value="{{ old("amount") }}" required>
                                            <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Receiver Email") }} <span class="text--base">*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("Enter Email") }}" name="receiver" value="{{ old("receiver") }}" required>
                                        <code class="d-block mt-10 text--warning text-end">{{ __("Available Balance") }} {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                                    </div>
                                    <div class="col-xl-12 col-lg-12 form-group">
                                        <div class="note-area">
                                            <code class="d-block limit-show">--</code>
                                            <code class="d-block fees-show">--</code>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12">
                                        <button type="submit" class="btn--base w-100">{{ __("Proceed") }} <i class="fas fa-arrow-alt-circle-right ms-1"></i></button>
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
                                                <i class="las la-paper-plane"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Sending Amount") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="sending-amount">--</span>
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
                                        <span>1 {{ $default_currency->code }} = {{ $exchange_rate }} {{ $default_currency->code }}</span>
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
                                                <span>{{ __("Recipient's Will Get") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="will-get">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="lab la-get-pocket"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Pay In Total") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--base payable">--</span>
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
                <h4 class="title">{{ __("Send Money Log") }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <a href="{{ setRoute('user.history.transaction','send-money-log') }}" class="btn--base">{{ __("View More") }}</a>
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

        let fixedCharge = "{{ $money_transfer_settings->fixed_charge ?? 0 }}";
        let percentCharge = "{{ $money_transfer_settings->percent_charge ?? 0 }}";
        let minLimit = "{{ $money_transfer_settings->min_limit ?? 0 }}";
        let maxLimit = "{{ $money_transfer_settings->max_limit ?? 0 }}";
        let defaultCurrency = "{{ get_default_currency_code() }}";

        $(document).ready(function() {
            run();
        });

        $(".sender-amount").keyup(function() {
            run();
        });

        function run() {
            let senderAmountInput = $(".sender-amount");
            let senderAmount = senderAmountInput.val();

            (senderAmount == null || senderAmount == "") ? senderAmount = 0 : senderAmount = senderAmount;

            if(!$.isNumeric(senderAmount)) return false;

            $(".limit-show").text(`• {{ __("Limit") }} ${parseFloat(minLimit).toFixed(4)} ${defaultCurrency} -  ${parseFloat(maxLimit).toFixed(4)} ${defaultCurrency} `);
            $(".fees-show").text(`• {{ __("Charge:") }} ${parseFloat(fixedCharge).toFixed(4)} ${defaultCurrency} = ${parseFloat(percentCharge).toFixed(4)}% `);

            $('.sending-amount').text(`${senderAmount} ${defaultCurrency}`);

            let percentChargeCalc = (parseFloat(senderAmount) / 100) * parseFloat(percentCharge);

            let totatCharge = parseFloat(percentChargeCalc) + parseFloat(fixedCharge);

            $(".fees").text(`${parseFloat(totatCharge).toFixed(4)} ${defaultCurrency}`);

            $(".will-get").text(`${parseFloat(senderAmount).toFixed(4)} ${defaultCurrency}`);

            let totalPayable = parseFloat(senderAmount) + parseFloat(totatCharge);

            $(".payable").text(`${parseFloat(totalPayable).toFixed(4)} ${defaultCurrency}`);

        }

        var timeOut;
        $("input[name=receiver]").bind("keyup",function(){
            clearTimeout(timeOut);
            timeOut = setTimeout(getUser, 500,$(this).val(),"{{ setRoute('user.info') }}",$(this));
        });

        function getUser(string,URL,errorPlace = null) {
            if(string.length < 3) {
                return false;
            }

            var CSRF = laravelCsrf();
            var data = {
                _token      : CSRF,
                text        : string,
            };

            $.post(URL,data,function() {
                // success
            }).done(function(response){
                if(response.data == null) {
                    if(errorPlace != null) {
                        $(errorPlace).css('border','1px solid rgba(153, 153, 153, 0.2)');
                        if($(errorPlace).parent().find(".get-user-error").length > 0) {
                            $(errorPlace).parent().find(".get-user-error").text("User doesn't exists");
                        }else {
                            $(`<span class="text--danger get-user-error mt-1" style="font-size:14px">User doesn't exists!</span>`).insertAfter($(errorPlace));
                        }
                    }

                }else {
                    if(errorPlace != null) {
                        $(errorPlace).parent().find(".get-user-error").remove();
                        $(errorPlace).css('border','2px solid green');
                    }
                }
            }).fail(function(response) {
                var response = JSON.parse(response.responseText);
                throwMessage(response.type,response.message.error);
            });
        }
    </script>
@endpush
