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
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.setup.sections.section.update',$slug) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row justify-content-center mb-10-none">
                    <div class="col-xl-12 col-lg-12">
                        <div class="product-tab">
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                    @foreach ($languages as $item)
                                        <button class="nav-link @if (get_default_language_code() == $item->code) active @endif" id="{{$item->name}}-tab" data-bs-toggle="tab" data-bs-target="#{{$item->name}}" type="button" role="tab" aria-controls="{{ $item->name }}" aria-selected="true">{{ $item->name }}</button>
                                    @endforeach
                                </div>
                            </nav>
                            <div class="tab-content" id="nav-tabContent">
                                @foreach ($languages as $item)
                                    @php
                                        $lang_code = $item->code;
                                    @endphp
                                    <div class="tab-pane @if (get_default_language_code() == $item->code) fade show active @endif" id="{{ $item->name }}" role="tabpanel" aria-labelledby="english-tab">
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'         => __("Section Title"),
                                                'label_after'   => "*",
                                                'name'          => $item->code . "_section_title",
                                                'value'         => old($item->code . "_section_title",$data->value->language->$lang_code->section_title ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'         => __("Heading"),
                                                'label_after'   => "*",
                                                'name'          => $item->code . "_heading",
                                                'value'         => old($item->code . "_heading",$data->value->language->$lang_code->heading ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'         => __("Sub Heading"),
                                                'label_after'   => "*",
                                                'name'          => $item->code . "_sub_heading",
                                                'value'         => old($item->code . "_sub_heading",$data->value->language->$lang_code->sub_heading ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.textarea',[
                                                'label'         => __("Description"),
                                                'label_after'   => "*",
                                                'name'          => $item->code . "_description",
                                                'value'         => old($item->code . "_description",$data->value->language->$lang_code->description ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Button Name"),
                                                'label_after'   => "*",
                                                'name'      => $item->code . "_button_name",
                                                'value'     => old($item->code . "_button_name",$data->value->language->$lang_code->button_name ?? "")
                                            ])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Section Icon"),
                                'label_after'   => "*",
                                'name'          => "section_icon",
                                'value'         => old("section_icon",$data->value->section_icon ?? ""),
                                'class'         => "form--control icp icp-auto iconpicker-element iconpicker-input",
                            ])
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label for="">{{ __("Button Link") }}*</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text" id="basic-addon1">{{ url('/') }}/</span>
                                <input type="text" class="form--control" name="button_link" value="{{ old("button_link",$data->value->button_link ?? "") }}">
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Button Icon"),
                                'label_after'   => "*",
                                'name'          => "button_icon",
                                'value'         => old("button_icon",$data->value->button_icon ?? ""),
                                'class'         => "form--control icp icp-auto iconpicker-element iconpicker-input",
                            ])
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => __("Update"),
                            'permission'    => "admin.setup.sections.section.update"
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="table-area mt-15">
        <div class="table-wrapper">
            <div class="table-header justify-content-end">
                <div class="table-btn-area">
                    <a href="#overview-add" class="btn--base modal-btn"><i class="fas fa-plus me-1"></i> {{ __("Add Overview Item") }}</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __("Title") }}</th>
                            <th>{{ __("Amount") }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data->value->items ?? [] as $key => $item)
                            <tr data-item="{{ json_encode($item) }}">

                                <td><span class="text--dark">{{ $item->language->$system_default_lang->title ?? "" }}</span></td>
                                <td>{{ $item->item_no }}</td>
                                <td>
                                    <button class="btn btn--base edit-modal-button"><i class="las la-pencil-alt"></i></button>
                                    <button class="btn btn--base btn--danger delete-modal-button" ><i class="las la-trash-alt"></i></button>
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 4])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.components.modals.site-section.add-overview-item')

    {{-- Overview Item Edit Modal --}}
    <div id="overview-edit" class="mfp-hide large">
        <div class="modal-data">
            <div class="modal-header px-0">
                <h5 class="modal-title">{{ __("Edit Overview item") }}</h5>
            </div>
            <div class="modal-form-data">
                <form class="modal-form" method="POST" action="{{ setRoute('admin.setup.sections.section.item.update',$slug) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="target" value="{{ old('target') }}">
                    <div class="row mb-10-none mt-3">
                        <div class="language-tab">
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                    @foreach ($languages as $item)
                                        <button class="nav-link @if (get_default_language_code() == $item->code) active @endif" id="edit-modal-{{$item->name}}-tab" data-bs-toggle="tab" data-bs-target="#edit-modal-{{$item->name}}" type="button" role="tab" aria-controls="edit-modal-{{ $item->name }}" aria-selected="true">{{ $item->name }}</button>
                                    @endforeach
                                </div>
                            </nav>
                            <div class="tab-content" id="nav-tabContent">

                                @foreach ($languages as $item)
                                    @php
                                        $lang_code = $item->code;
                                    @endphp
                                    <div class="tab-pane @if (get_default_language_code() == $item->code) fade show active @endif" id="edit-modal-{{ $item->name }}" role="tabpanel" aria-labelledby="edit-modal-{{$item->name}}-tab">
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Title"),
                                                'label_after'=> "*",
                                                'name'      => $lang_code . "_title_edit",
                                                'value'     => old($lang_code . "_title_edit",$data->value->language->$lang_code->title ?? "")
                                            ])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                @include('admin.components.form.input',[
                                    'label'     => __("Item No"),
                                    'label_after'=> "*",
                                    'type'      => "number",
                                    'name'      => "item_no_edit",
                                    'value'     => old("item_no_edit",$data->value->item_no ?? "")
                                ])
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group d-flex align-items-center justify-content-between mt-4">
                            <button type="button" class="btn btn--danger modal-close">{{ __("Cancel") }}</button>
                            <button type="submit" class="btn btn--base">{{ __("Update") }}</button>
                        </div>
                    </div>
                </form>
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
        openModalWhenError("overview-add","#overview-add");
        openModalWhenError("overview-edit","#overview-edit");

        var default_language = "{{ $default_lang_code }}";
        var system_default_language = "{{ $system_default_lang }}";
        var languages = "{{ $languages_for_js_use }}";
        languages = JSON.parse(languages.replace(/&quot;/g,'"'));

        $(".edit-modal-button").click(function(){
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
            var editModal = $("#overview-edit");

            editModal.find("form").first().find("input[name=target]").val(oldData.id);

            $.each(languages,function(index,item) {
                editModal.find("input[name="+item.code+"_title_edit]").val(oldData.language[item.code]?.title);
            });
            editModal.find("input[name=item_no_edit]").val(oldData.item_no);
            fileHolderPreviewReInit("#overview-edit input[name=image_edit]");
            openModalBySelector("#overview-edit");

        });

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


