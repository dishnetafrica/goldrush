@extends('user.layouts.master')

@push('css')

@endpush

@section('content')
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ $page_title }}</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="dash-payment-item-wrapper">
                        @include('user.components.profile.kyc',compact("kyc_data"))
                        @if ($user->kyc_verified != global_const()::VERIFIED && $user->kyc_verified != global_const()::PENDING)
                            <div class="send-add-form row">
                                <div class="col-lg-10 col-md-10 col-12 form-area">
                                    <div class="add-money-text pb-3">
                                        <span class="text-white">{{ __("Fill up information and verify your KYC.") }}</span>
                                    </div>
                                    <form class="card-form" method="POST" action="{{ setRoute('user.kyc.submit') }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row">
                                            @include('user.components.generate-kyc-fields',['fields' => $kyc_fields])

                                            <div class="col-xl-12 col-lg-12">
                                                <button type="submit" class="btn--base w-100">{{ __("Submit") }} <i class="fas fa-arrow-alt-circle-right ms-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                </div>
            </div>
        </div>
@endsection

@push('script')

@endpush
