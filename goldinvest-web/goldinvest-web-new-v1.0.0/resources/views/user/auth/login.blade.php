@extends('layouts.master')
@section('content')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Account
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="account-section login">
    @if(@isset($auth->value))
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-5 col-md-12">
                    <div class="account-wrapper">
                        <div class="account-form-area">
                            <div class="account-logo text-center">
                                <a class="site-logo site-title" href="{{setRoute("frontend.index")}}"><img src="{{ get_logo($basic_settings,'dark') }}" alt="site-logo"></a>
                            </div>
                            <h4 class="title">{{  @$auth->value->language->$lang->login_heading ?? @$auth->value->language->$default->login_heading }}</h4>
                            <p>{{  @$auth->value->language->$lang->login_sub_heading ?? @$auth->value->language->$default->login_sub_heading }}</p>
                            <form action="{{ setRoute('user.login.submit') }}" class="account-form"  method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12 form-group">
                                        <label>{{ __("Email Address") }} <span class="text--base">*</span></label>
                                        <input type="text" class="form-control form--control" name="credentials" placeholder="{{ __("Username OR Email Address") }}" required>
                                    </div>
                                    <div class="col-lg-12 form-group show_hide_password">
                                        <label>{{ __("Password") }} <span class="text--base">*</span></label>
                                        <input type="password" class="form-control form--control" name="password" placeholder="{{ __("Password") }}" required>
                                        <a href="javascript:void(0)" class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                                    </div>
                                    <div class="col-lg-12 form-group">
                                        <div class="forgot-item">
                                            <label><a href="{{setRoute('user.password.forgot')}}" class="text--base">{{ __("Forgot Password") }}?</a></label>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 form-group text-center">
                                        <button type="submit" class="btn--base w-100">{{ __("Login Now") }}</button>
                                    </div>
                                    @if($basic_settings->user_registration)
                                    <div class="col-lg-12">
                                        <div class="account-item text-center mt-10">
                                            <label>{{ __("Don't Have An Account?") }} <a href="{{setRoute('user.register')}}" class="text--base">{{ __("Register Now") }}</a></label>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Account
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

<!-- particles.js container -->
<div id="particles-js"></div>

@endsection
