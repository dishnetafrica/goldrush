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
                            <h5 class="title">{{ __("Delivery Address") }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <form class="card-form" action="{{ setRoute("user.investment.gold.order", $gold->slug) }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Country") }}<span>*</span></label>
                                        <select name="country" class="form--control select2-auto-tokenize country-select" data-placeholder="{{ __("Select Country") }}" data-old="{{ old('country',auth()->user()->address->country ?? "") }}"></select>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Phone") }}<span>*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-text phone-code">+{{ auth()->user()->mobile_code }}</div>
                                            <input class="phone-code" type="hidden" name="phone_code" value="{{ auth()->user()->mobile_code }}" />
                                            <input type="text" class="form--control" placeholder="Enter Phone ..." name="phone" value="{{ old('phone',auth()->user()->mobile) }}">
                                        </div>
                                        @error("phone")
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @php
                                            $old_state = old('state',auth()->user()->address->state ?? "");
                                        @endphp
                                        <label>{{ __("State") }}<span>*</span></label>
                                        <select name="state" class="form--control select2-auto-tokenize state-select" data-placeholder="Select State" data-old="{{ $old_state }}">
                                            @if ($old_state)
                                                <option value="{{ $old_state }}" selected>{{ $old_state }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @php
                                            $old_city = old('city',auth()->user()->address->city ?? "");
                                        @endphp
                                        <label>{{ __("City") }}<span>*</span></label>
                                        <select name="city" class="form--control select2-auto-tokenize city-select" data-placeholder="Select City" data-old="{{ $old_city }}">
                                            @if ($old_city)
                                                <option value="{{ $old_city }}" selected>{{ $old_city }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @include('admin.components.form.input',[
                                            'label'         => __("Zip Code"),
                                            'label_after'   => "*",
                                            'name'          => "zip_code",
                                            'placeholder'   => "Enter Zip...",
                                            'value'         => old('zip_code',auth()->user()->address->zip ?? "")
                                        ])
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @include('admin.components.form.input',[
                                            'label'         => __("Address"),
                                            'label_after'   => "*",
                                            'name'          => "address",
                                            'placeholder'   => "Enter Address...",
                                            'value'         => old('address',auth()->user()->address->address ?? "")
                                        ])
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __("Summary") }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <div class="preview-list-wrapper">
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-sort-numeric-up"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Quantity") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <div class="product-plus-minus">
                                            <div class="dec qtybutton">-</div>
                                            <input class="product-plus-minus-box" type="text"  name="qtybutton" value="1">
                                            <div class="inc qtybutton">+</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-weight"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Weight") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span>{{ @$gold->weight }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-procedures"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Manufacturer") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span>{{ @$gold->manufacturer }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-coins"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Purity") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span>{{ get_amount(@$gold->purity) }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-hand-holding-usd"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Amount") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span>{{ get_amount(@$gold->price) }} {{ $default_currency->code }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-truck"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Delivery Charge") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span>{{ get_amount(@$gold->charge) }} {{ $default_currency->code }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-dollar-sign"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Total Payable Amount") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span id="total-payable-amount"></span>
                                    </div>
                                </div>
                                <div class="col-xxl-6 col-xl-12 col-lg-6 col-md-6 col-sm-6 form-group">
                                    <label>{{ __("Payment Type") }} <span class="text--base">*</span></label>
                                    <div class="custom-check-group">
                                        <input type="radio" id="paymentTypeWallet" class="dependency-radio" name="payment_type" value="{{ global_const()::PAYMENT_TYPE_USER_WALLET }}">
                                        <label for="paymentTypeWallet">{{ __("User Wallet") }}</label>
                                    </div>
                                    <div class="custom-check-group">
                                        <input type="radio" id="paymentTypeCOD" class="dependency-radio" name="payment_type" value="{{ global_const()::PAYMENT_TYPE_CASH_ON_DELIVERY }}">
                                        <label for="paymentTypeCOD">{{ __("Cash On Delivery") }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="checkout-btn-area">
                            <button class="btn--base w-100 mt-30" value="submit">{{ __("Pay Now") }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        </div>

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@endsection
@push('script')
<script>
    getAllCountries("{{ setRoute('global.countries') }}",$(".country-select"));
               $(document).ready(function(){

                   $(".country-select").select2();

                   $("select[name=country]").change(function(){
                       var phoneCode = $("select[name=country] :selected").attr("data-mobile-code");
                       placePhoneCode(phoneCode);
                   });

                   setTimeout(() => {
                       var phoneCodeOnload = $("select[name=country] :selected").attr("data-mobile-code");
                       placePhoneCode(phoneCodeOnload);
                   }, 400);

                   countrySelect(".country-select",$(".country-select").siblings(".select2"));
                   stateSelect(".state-select",$(".state-select").siblings(".select2"));
               });
   </script>
<script>
    var qtybutton = 1;
    function updateTotalAmount() {
        var goldPrice = parseFloat("{{ $gold->price }}");
        var deliveryCharge = parseFloat("{{ $gold->charge }}");
        var totalAmount = goldPrice * qtybutton + deliveryCharge;

        $("#total-payable-amount").text(totalAmount + " {{ $default_currency->code }}");
        if (qtybutton === 1) {
            $(".dec.qtybutton").hide();
        } else {
            $(".dec.qtybutton").show();
        }
    }

    $(document).ready(function () {

        $(".inc.qtybutton").on("click", function () {
            qtybutton++;
            updateTotalAmount();
        });

        $(".dec.qtybutton").on("click", function () {
            if (qtybutton > 1) {
                qtybutton--;
                updateTotalAmount();
            }

        });


        $("input[name='qtybutton']").on("input", function () {
            var inputValue = $(this).val();
            if (!isNaN(inputValue) && inputValue >= 1) {
                qtybutton = parseInt(inputValue);
            } else {
                qtybutton = 1;
            }
            updateTotalAmount();
        });


        updateTotalAmount();
    });
</script>
@endpush
