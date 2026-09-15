@extends('layouts.master')
@section('content')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Account
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="account-section login">
    @if(@isset($auth->value))
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-6 col-md-12">
                    <div class="account-wrapper">
                        <div class="account-form-area">
                            <div class="account-logo text-center">
                                <a class="site-logo site-title" href="{{setRoute("frontend.index")}}"><img src="{{ get_logo($basic_settings,'dark')}}" alt="site-logo"></a>
                            </div>
                            <h4 class="title">{{  @$auth->value->language->$lang->forgot_heading ?? @$auth->value->language->$default->forgot_heading }}</h4>
                            <p>{{  @$auth->value->language->$lang->forgot_sub_heading ?? @$auth->value->language->$default->forgot_sub_heading }}</p>
                            <form class="account-form" action="{{ setRoute('user.password.forgot.send.code') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12 form-group">
                                        <input type="email" class="form-control form--control" name="credentials" placeholder="{{ __("Enter Email") }}" required>
                                    </div>
                                    <div class="col-lg-12 form-group text-center">
                                        <button type="submit" class="btn--base w-100">{{ __("Send OTP") }}</button>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="account-item text-center mt-10">
                                            <label>{{ __("Back to") }} <a href="{{ setRoute('frontend.index') }}"  class="text--base">{{ __("Home") }}</a></label>
                                        </div>
                                    </div>
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
