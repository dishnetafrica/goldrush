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
                      <h4 class="title">{{ __("Two Factor Authorization") }}</h4>
                      <p>{{ __("Please enter your authorization code to access dashboard.") }}</p>
                      <form class="account-form"  action="{{ setRoute('user.authorize.google.2fa.submit') }}" method="POST">
                        @csrf
                          <div class="row ml-b-20">
                              <div class="col-lg-12 form-group">
                                <input type="text" class="form-control number-input" name="code" placeholder="Enter Authorization Code" value="">
                              </div>
                              <div class="col-lg-12 form-group text-center">
                                  <button type="submit" class="btn--base w-100">{{ __("Submit") }}</button>
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

@endpush
