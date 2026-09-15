@extends('layouts.master')
@section('content')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Account
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="account-section login">
  <div class="container">
      <div class="row justify-content-center align-items-center">
          <div class="col-lg-5 col-md-12">
              <div class="account-wrapper">
                  <div class="account-form-area text-center">
                      <div class="account-logo text-center">
                          <a class="site-logo site-title" href="{{setRoute("frontend.index")}}"><img src="{{ get_logo($basic_settings,'dark')}}" alt="site-logo"></a>
                      </div>
                      <h4 class="title">{{ __('Please Enter the Code') }}</h4>
                      <p>{{ __("Please check your email address to get the OTP (One time password).") }}</span></p>
                      <form class="account-form" action="{{ setRoute('user.password.forgot.verify.code',$token) }}" method="POST">
                        @csrf
                          <div class="row ml-b-20">
                              <div class="col-lg-12 form-group">
                                  <input class="otp" type="text" name="code[]" oninput='digitValidate(this)' onkeyup='tabChange(1)'
                                      maxlength=1 required>
                                  <input class="otp" type="text" name="code[]" oninput='digitValidate(this)' onkeyup='tabChange(2)'
                                      maxlength=2 required>
                                  <input class="otp" type="text" name="code[]" oninput='digitValidate(this)' onkeyup='tabChange(3)'
                                      maxlength=1 required>
                                  <input class="otp" type="text" name="code[]" oninput='digitValidate(this)' onkeyup='tabChange(4)'
                                      maxlength=1 required>
                                  <input class="otp" type="text" name="code[]" oninput='digitValidate(this)' onkeyup='tabChange(5)'
                                      maxlength=1 required>
                                  <input class="otp" type="text" name="code[]" oninput='digitValidate(this)' onkeyup='tabChange(6)'
                                      maxlength=1 required>
                              </div>
                              <div class="col-lg-12 form-group text-end">
                                  <div class="time-area">{{ __("You can resend the code after") }} <span id="time"></span></div>
                              </div>
                              <div class="col-lg-12 form-group text-center">
                                  <button type="submit" class="btn--base w-100">{{ __("Submit") }}</button>
                              </div>
                              <div class="col-lg-12 text-center">
                                  <div class="account-item">
                                      <label>{{ __("Back to") }} <a href="{{ setRoute('frontend.index') }}" class="account-control-btn">{{__("Home")}}
                                            </a></label>
                                  </div>
                              </div>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      </div>
  </div>
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  End Account
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

<!-- particles.js container -->
<div id="particles-js"></div>

@endsection
@push('script')
<script>
    let digitValidate = function (ele) {
        console.log(ele.value);
        ele.value = ele.value.replace(/[^0-9]/g, '');
    }

    let tabChange = function (val) {
        let ele = document.querySelectorAll('.otp');
        if (ele[val - 1].value != '') {
            ele[val].focus()
        } else if (ele[val - 1].value == '') {
            ele[val - 2].focus()
        }
    }
</script>

<script>
    var convertAsSecond = "{{ global_const()::USER_PASS_RESEND_TIME_MINUTE }}" * 60;
    function resetTime (second = convertAsSecond) {
    var coundDownSec = second;
    var countDownDate = new Date();
    countDownDate.setMinutes(countDownDate.getMinutes() + 120);
    var x = setInterval(function () {
        var now = new Date().getTime();
        var distance = countDownDate - now;
        var minutes = Math.floor((distance % (1000 * coundDownSec)) / (1000 * coundDownSec));
        var seconds = Math.floor((distance % (1000 * coundDownSec)) / 1000);
        document.getElementById("time").innerHTML =seconds + "s ";

        if (distance < 0 || second < 2 ) {

            clearInterval(x);

            document.querySelector(".time-area").innerHTML = "Didn't get the code? <a href='{{ setRoute('user.password.forgot.resend.code',$token) }}' onclick='resendOtp()' class='text--danger'>Resend</a>";
        }

        second--
    }, 1000);
}

resetTime();
</script>

<script>
    $(".otp").parents("form").find("input[type=submit],button[type=submit]").click(function(e){
        // e.preventDefault();
        var otps = $(this).parents("form").find(".otp");
        var result = true;
        $.each(otps,function(index,item){
            if($(item).val() == "" || $(item).val() == null) {
                result = false;
            }
        });

        if(result == false) {
            $(this).parents("form").find(".otp").addClass("required");
        }else {
            $(this).parents("form").find(".otp").removeClass("required");
            $(this).parents("form").submit();
        }
    });
</script>

@endpush
