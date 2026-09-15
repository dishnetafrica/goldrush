@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 194px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 150px !important;
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
    ], 'active' => __("Admin Care")])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __("All Admin Roles") }}</h5>
                <div class="table-btn-area">
                    @include('admin.components.link.add-default',[
                        'href'          => "#role-add",
                        'class'         => "modal-btn",
                        'text'          => __("Add New"),
                        'permission'    => "admin.admins.role.store",
                    ])
                </div>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __("SL NO") }}</th>
                            <th>{{ __("Role Name") }}</th>
                            <th>{{ __("Asign Admin") }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $key => $item)
                            <tr data-item="{{ $item->editData }}">
                                <td>{{ $key + 1 }}</td>
                                <td><span>{{ $item->name }}</span></td>
                                <td>{{ $item->assignRole->count() }}</td>
                                <td>
                                    @if ($item->name != admin_role_const()::SUPER_ADMIN)
                                        @include('admin.components.link.edit-default',[
                                            'class'         => "edit-modal-button",
                                            'permission'    => "admin.admins.role.update",
                                        ])
                                        @include('admin.components.link.delete-default',[
                                            'class'         => "role-delete-btn",
                                            'permission'    => "admin.admins.role.delete",
                                        ])
                                    @endif
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

    {{-- Add Admin Role Modal --}}
    @include('admin.components.modals.admin-role-add')

    {{-- Edit Admin Role Modal --}}
    @include('admin.components.modals.admin-role-edit')

@endsection

@push('script')
<script>

    $(".role-delete-btn").click(function(){
        var oldData = JSON.parse($(this).parents("tr").attr("data-item"));

        var actionRoute =  "{{ setRoute('admin.admins.role.delete') }}";
        var target      = oldData.id;
        var message     =`{{ __("Are you sure to delete this role") }} ?`;
        openDeleteModal(actionRoute,target,message);
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
        function openModalByContent(data = {
            content:"",
            animation: "mfp-move-horizontal",
            size: "medium",
            }) {
            $.magnificPopup.open({
                removalDelay: 500,
                items: {
                src: `<div class="white-popup mfp-with-anim ${data.size ?? "medium"}">${data.content}</div>`, // can be a HTML string, jQuery object, or CSS selector
                },
                callbacks: {
                beforeOpen: function() {
                    this.st.mainClass = data.animation ?? "mfp-move-horizontal";
                },
                open: function() {
                    var modalCloseBtn = this.contentContainer.find(".modal-close");
                    $(modalCloseBtn).click(function() {
                    $.magnificPopup.close();
                    });
                },
                },
                midClick: true,
            });
        }
        function laravelCsrf() {
            return $("head meta[name=csrf-token]").attr("content");
        }
    });

</script>
@endpush
