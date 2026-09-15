@extends('user.layouts.master')
@section('content')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
        <div class="row mb-20-none">
            <div class="col-xl-12 col-lg-12 mb-20">
                <div class="custom-card mt-10">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ $page_title }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="card-form"  action="{{ route('user.support.ticket.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group">
                                    @include('admin.components.form.input',[
                                        'label'         => __("Subject")."<span>*</span>",
                                        'name'          => "subject",
                                        'value'         => old("subject"),
                                        'placeholder'   => __("Enter Subject")."...",
                                    ])
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    @include('admin.components.form.textarea',[
                                        'label'         => __('Message').'<span>*</span>',
                                        'name'          => "desc",
                                        'value'         => old("desc"),
                                        'placeholder'   => __("Write Here")."...",
                                    ])
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                        @include('admin.components.form.input-file',[
                                            'label'          => __("Attachments").'<span class="text--base">'.'('.__("Optional").')'.'</span>',
                                            'name'           => "attachment[]",
                                            'class'          => "file-holder-wrapper",
                                            'data-height'    => 130,
                                            'data-max_size'  => 20,
                                            'data-file_limit'=> 15,
                                            'attribute'      => "multiple",
                                            'id'             => "fileUpload",
                                        ])
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12">
                                <button type="submit" class="btn--base w-100">{{ __("Add New") }} <i class="fas fa-plus-circle ms-1"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

@endsection
