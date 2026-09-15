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
                                <a class="site-logo site-title" href="{{setRoute("frontend.index")}}"><img src="{{ get_logo($basic_settings,'dark') }}" alt="site-logo"></a>
                            </div>
                            <h4 class="title">{{  @$auth->value->language->$lang->register_heading ?? @$auth->value->language->$default->register_heading }}</h4>
                            <p>{{  @$auth->value->language->$lang->register_sub_heading ?? @$auth->value->language->$default->register_sub_heading }}</p>
                            <form action="{{ setRoute('user.register.submit') }}" class="account-form" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6 form-group">
                                        <label>{{ __("First Name") }} <span class="text--base">*</span></label>
                                        <input type="text" class="form-control form--control" name="firstname" placeholder="{{ __("First Name") }}" required>
                                    </div>
                                    <div class="col-lg-6 form-group">
                                        <label>{{ __("Last Name") }}<span class="text--base">*</span></label>
                                        <input type="text" class="form-control form--control" name="lastname" placeholder="{{ __("Last Name") }}" required>
                                    </div>
                                    <div class="col-lg-12 form-group">
                                        <label>{{ __("Email") }}<span class="text--base">*</span></label>
                                        <input type="email" class="form-control form--control" name="email" placeholder="{{ __("Enter Email") }}" required>
                                    </div>
                                    <div class="col-lg-12 form-group show_hide_password">
                                        <label>{{ __("Password") }} <span class="text--base">*</span></label>
                                        <input type="password" class="form-control form--control" name="password" placeholder="{{ __("Enter Password") }}" required>
                                        <a href="javascript:void(0)" class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                                    </div>
                                    @if (@$referral_settings->status)
                                    <div class="col-lg-12 form-group">
                                        <label>{{ __("Referral") }}</label>
                                            <input type="text" class="form-control form--control" name="refer" placeholder="{{ __("Enter Referral Id") }}" value="{{ $refer }}">
                                        </div>
                                    @endif
                                    @if (@$basic_settings->agree_policy == 1)
                                    @php
                                        $useful_link = App\Models\Admin\UsefulLink::where('slug','privacy-policy')->where('status', 1)->first();
                                    @endphp
                                        <div class="col-lg-12 form-group">
                                            <div class="custom-check-group">
                                                <input type="checkbox" id="level-1" name="agree">
                                                <label for="level-1">{{ __("I have agreed with") }}  @if ($useful_link != null)  <a href="{{ setRoute('frontend.useful.links',$useful_link->slug) }}" class="text--base">{{ __("Terms Of Use") }} &amp; {{ __("Privacy Policy") }}</a>@endif </label>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-lg-12 form-group text-center">
                                        <button type="submit" class="btn--base w-100">{{ __("Register Now") }}</button>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="account-item text-center mt-10">
                                            <label>{{ __("Already Have An Account") }}? <a href="{{setRoute('user.login')}}" class="text--base">{{ __("Login") }}</a></label>
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
