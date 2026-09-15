@php
    $default_lang_code = language_const()::NOT_REMOVABLE;
    $system_default_lang = get_default_language_code();
    $languages_for_js_use = $languages->toJson();
@endphp

@extends('admin.layouts.master')

@push('css')
    <link rel="stylesheet" href="{{ asset('public/backend/css/fontawesome-iconpicker.min.css') }}">
    <style>
        .fileholder {
            min-height: 374px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 330px !important;
        }
    </style>
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
    ], 'active' => __("Setup Section")])
@endsection

@section('content')
    <div class="custom-card mt-5">
        <div class="card-header">
            <h6 class="title">{{ __("Announcement Dashboard") }}</h6>
            <div class="button-link">
                @include('admin.components.link.custom',[
                    'text'          => __('Categories'),
                    'class'         => 'btn btn--primary',
                    'href'          => setRoute('admin.setup.sections.announcement.category.index'),
                    'permission'    => 'admin.setup.sections.announcement.category.index',
                ])
                @include('admin.components.link.custom',[
                    'text'          => __('Announcements'),
                    'class'         => 'btn btn--base',
                    'href'          => setRoute('admin.setup.sections.announcement.index'),
                    'permission'    => 'admin.setup.sections.announcement.index',
                ])
            </div>
        </div>

        <div class="card-body">
            <div class="dashboard-area">
                <div class="dashboard-item-area">
                    <div class="row">
                        <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                            <div class="dashbord-item border">
                                <div class="dashboard-content">
                                    <div class="left">
                                        <h6 class="title">{{ __("Total Category") }}</h6>
                                        <div class="user-info">
                                            <h2 class="user-count">{{ $total_categories }}</h2>
                                        </div>
                                    </div>
                                    <div class="right">
                                        <div class="chart" id="chart6" data-percent="{{ get_percentage_from_two_number($total_categories,$total_categories) }}"><span>{{ get_percentage_from_two_number($total_categories,$total_categories) }}%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                            <div class="dashbord-item border">
                                <div class="dashboard-content">
                                    <div class="left">
                                        <h6 class="title">{{ __("Active Category") }}</h6>
                                        <div class="user-info">
                                            <h2 class="user-count">{{ $active_categories }}</h2>
                                        </div>
                                    </div>
                                    <div class="right">
                                        <div class="chart" id="chart7" data-percent="{{ get_percentage_from_two_number($total_categories,$active_categories) }}"><span>{{ get_percentage_from_two_number($total_categories,$active_categories) }}%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                            <div class="dashbord-item border">
                                <div class="dashboard-content">
                                    <div class="left">
                                        <h6 class="title">{{ __("Total Announcement") }}</h6>
                                        <div class="user-info">
                                            <h2 class="user-count">{{ $total_announcements }}</h2>
                                        </div>
                                    </div>
                                    <div class="right">
                                        <div class="chart" id="chart8" data-percent="{{ get_percentage_from_two_number($total_announcements,$total_announcements) }}"><span>{{ get_percentage_from_two_number($total_announcements,$total_announcements) }}%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                            <div class="dashbord-item border">
                                <div class="dashboard-content">
                                    <div class="left">
                                        <h6 class="title">{{ __("Active Announcement") }}</h6>
                                        <div class="user-info">
                                            <h2 class="user-count">{{ $active_announcements }}</h2>
                                        </div>
                                    </div>
                                    <div class="right">
                                        <div class="chart" id="chart9" data-percent="{{ get_percentage_from_two_number($total_announcements,$active_announcements) }}"><span>{{ get_percentage_from_two_number($total_announcements,$active_announcements) }}%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('public/backend/js/fontawesome-iconpicker.js') }}"></script>
    <script>
        // icon picker
        $('.icp-auto').iconpicker();
    </script>
    <script>
        openModalWhenError("testimonial-add","#testimonial-add");

        var default_language = "{{ $default_lang_code }}";
        var system_default_language = "{{ $system_default_lang }}";
        var languages = "{{ $languages_for_js_use }}";
        languages = JSON.parse(languages.replace(/&quot;/g,'"'));

        $(".delete-modal-button").click(function(){
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));

            var actionRoute =  "{{ setRoute('admin.setup.sections.section.item.delete',$slug) }}";
            var target = oldData.id;
            var message     = `{{ __("Are you sure to delete this item?") }}`;

            openDeleteModal(actionRoute,target,message);
        });

        function openDeleteModal(URL,target,message,actionBtnText = "{{ __('Remove') }}",method = "DELETE"){
            if(URL == "" || target == "") {
                return false;
            }

            if(message == "") {
                message = "{{ __('Are you sure to delete ?') }}";
            }
            var method = `<input type="hidden" name="_method" value="${method}">`;
            openModalByContent(
                {
                    content: `<div class="card modal-alert border-0">
                                <div class="card-body">
                                    <form method="POST" action="${URL}">
                                        <input type="hidden" name="_token" value="${laravelCsrf()}">
                                        ${method}
                                        <div class="head mb-3">
                                            ${message}
                                            <input type="hidden" name="target" value="${target}">
                                        </div>
                                        <div class="foot d-flex align-items-center justify-content-between">
                                            <button type="button" class="modal-close btn btn--info">{{ __("Close") }}</button>
                                            <button type="submit" class="alert-submit-btn btn btn--danger btn-loading">${actionBtnText}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>`,
                },

            );
            }
    </script>
@endpush
