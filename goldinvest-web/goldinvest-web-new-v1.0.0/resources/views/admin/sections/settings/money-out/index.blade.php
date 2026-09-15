@extends('admin.layouts.master')

@push('css')

@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Money Out Setting")])
@endsection

@section('content')

    <div class="custom-card">

        <div class="card-header">
            <h6 class="title">{{ __("Enable/Disable Money Out Features") }}</h6>
        </div>

        <div class="card-body">
            <form class="card-form" method="POST" action="{{ setRoute('admin.settings.money.out.update') }}">
                @csrf
                <div class="title mb-3 fw-bold">{{ __('Accessibale wallet for money out') }}</div>

                <div class="row">
                    <div class="col-3 mb-4">
                        @include('admin.components.form.switcher',[
                            'label'         => __('Current Balance'),
                            'name'          => 'c_balance',
                            'value'         => old('c_balance',$money_out_settings->c_balance ?? 0),
                            'options'       => [__('Enable') => 1,__('Disable') => 0],
                        ])
                    </div>
                    <div class="col-3 mb-4">
                        @include('admin.components.form.switcher',[
                            'label'         => __('Profit Balance'),
                            'name'          => 'p_balance',
                            'value'         => old('p_balance',$money_out_settings->p_balance ?? 0),
                            'options'       => [__('Enable') => 1,__('Disable') => 0],
                        ])
                    </div>
                </div>

                <div class="col-xl-12 col-lg-12">
                    @include('admin.components.button.form-btn',[
                        'class'         => "w-100 btn-loading",
                        'text'          => __("Update"),
                    ])
                </div>
            </form>
        </div>

    </div>

@endsection

@push('script')

@endpush
