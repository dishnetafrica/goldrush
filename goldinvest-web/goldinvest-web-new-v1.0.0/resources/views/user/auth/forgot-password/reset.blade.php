@extends('layouts.master')
@section('content')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Account
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="account-section login">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-lg-6 col-md-12">
                <div class="account-wrapper">
                    <div class="account-form-area">
                        <div class="account-logo text-center">
                            <a class="site-logo site-title" href="{{setRoute("frontend.index")}}"><img src="{{ get_logo($basic_settings,'dark')}}" alt="site-logo"></a>
                        </div>
                        <h4 class="title">{{ __("Set A New Password") }}</h4>
                        <p>{{ __("Kindly Enter your new secret key and get login access on your Dashboard.") }}</p>
                        <form class="account-form" action="{{ setRoute('user.password.reset',$token) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 form-group show_hide_password">
                                    <label>{{ __("New Password") }} <span class="text--base">*</span></label>
                                    <input type="password" class="form-control form--control" name="password" placeholder="{{ __("Enter New Password") }}" required>
                                    <a href="javascript:void(0)" class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                                </div>
                                <div class="col-lg-12 form-group show_hide_password">
                                    <label>{{ __("Confirm Password ") }}<span class="text--base">*</span></label>
                                    <input type="password" class="form-control form--control" name="password_confirmation" placeholder="{{ __("Confirm Password") }}" required>
                                    <a href="javascript:void(0)" class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                                </div>
                                <div class="col-lg-12 form-group text-center">
                                    <button type="submit" class="btn--base w-100">{{ __("Confirm") }}</button>
                                </div>
                                <div class="col-lg-12">
                                    <div class="account-item text-center mt-10">
                                        <label>{{ __("Back to") }} <a href="{{ setRoute('frontend.index') }}" class="text--base" data-block="login">{{__("Home")}}</a></label>
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
