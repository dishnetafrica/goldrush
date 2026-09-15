<div class="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-menu-inner-wrapper">
            <div class="sidebar-logo">
                <a href="{{setRoute("frontend.index")}}" class="sidebar-main-logo theme-change">
                    <img src="{{ get_logo($basic_settings,'dark') }}" alt="logo">
                </a>
                <button class="sidebar-menu-bar">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
            <div class="sidebar-menu-wrapper">
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item">
                        <a href="{{setRoute("user.dashboard")}}">
                            <i class="menu-icon fas fa-th-large"></i>
                            <span class="menu-title">{{ __("Dashboard") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-coins"></i>
                            <span class="menu-title">{{ __("Investment") }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                <a href="{{setRoute('user.investment.plan')}}" class="nav-link">
                                    <i class="menu-icon las la-ellipsis-h"></i>
                                    <span class="menu-title">{{ __("Gold Invest") }}</span>
                                </a>
                                <a href="{{setRoute('user.investment.gold.store')}}" class="nav-link">
                                    <i class="menu-icon las la-ellipsis-h"></i>
                                    <span class="menu-title">{{ __("Gold Store") }}</span>
                                </a>
                                <a href="{{setRoute('user.investment.invest')}}" class="nav-link">
                                    <i class="menu-icon las la-ellipsis-h"></i>
                                    <span class="menu-title">{{ __("My Invest") }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute("user.add.money.index") }}">
                            <i class="menu-icon fas fa-plus-circle"></i>
                            <span class="menu-title">{{ __("Add Money") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.withdraw.money.index') }}">
                            <i class="menu-icon fas fa-arrow-alt-circle-right"></i>
                            <span class="menu-title">{{ __("Money Out") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute("user.transfer.money.index") }}">
                            <i class="menu-icon fas fa-paper-plane"></i>
                            <span class="menu-title">{{ __("Send Money") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-clock"></i>
                            <span class="menu-title">{{ __("History") }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.history.profit')}}" class="nav-link">
                                    <i class="menu-icon las la-ellipsis-h"></i>
                                    <span class="menu-title">{{ __("Profit Log") }}</span>
                                </a>
                                <a href="{{ setRoute('user.history.transaction')}}" class="nav-link">
                                    <i class="menu-icon las la-ellipsis-h"></i>
                                    <span class="menu-title">{{ __("Transaction") }}</span>
                                </a>
                                <a href="{{ setRoute('user.history.order')}}" class="nav-link">
                                    <i class="menu-icon las la-ellipsis-h"></i>
                                    <span class="menu-title">{{ __("Order log") }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-shield-alt"></i>
                            <span class="menu-title">{{ __("Security") }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                @if ($basic_settings->kyc_verification)
                                <a href="{{ setRoute('user.kyc.index') }}" class="nav-link">
                                    <i class="menu-icon las la-ellipsis-h"></i>
                                    <span class="menu-title">{{ __("KYC Verification") }}</span>
                                </a>
                                @endif
                                <a href="{{ setRoute('user.security.google.2fa') }}" class="nav-link">
                                    <i class="menu-icon las la-ellipsis-h"></i>
                                    <span class="menu-title">{{ __("2FA Security") }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.status.index') }}">
                            <i class="menu-icon fas fa-level-up-alt"></i>
                            <span class="menu-title">{{ __("My Status") }}</span>
                            <div class="sidebar-item-badge">
                                <span class="badge">
                                    @php
                                        $title = auth()->user()->referLevel->title;
                                        preg_match('/\d+/', $title, $matches);
                                        echo (!empty($matches)) ? $matches[0] : '';
                                    @endphp
                                </span>
                            </div>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="javascript:void(0)" class="logout-btn">
                            <i class="menu-icon fas fa-sign-out-alt"></i>
                            <span class="menu-title">{{ __("Logout") }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="sidebar-doc-box bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}">
            <div class="sidebar-doc-icon">
                <i class="las la-headset"></i>
            </div>
            <div class="sidebar-doc-content">
                <h4 class="title">{{ __("Help Center") }}</h4>
                <p>{{ __("How can we help you") }}?</p>
                <div class="sidebar-doc-btn">
                    <a href="{{ setRoute('user.support.ticket.index') }}" class="btn--base w-100">{{ __("Get Support") }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@push('script')
<script>
    $(".logout-btn").click(function(){
        var actionRoute =  "{{ setRoute('user.logout') }}";
        var target      = 1;
        var message     = `{{ __("Are you sure to") }} <strong>{{ __("Logout") }}</strong>?`;
        openAlertModal(actionRoute,target,message,"{{ __('Logout') }}","POST");
    });
    function openAlertModal(URL, target, message, actionBtnText = "{{ __('Remove') }}", method = "DELETE") {
        if (URL == "" || target == "") {
            return false;
        }

        if (message == "") {
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
                                        <button type="button" class="modal-close btn--base btn-for-modal">{{ __("Close") }}</button>
                                        <button type="submit" class="alert-submit-btn btn btn--base bg--danger btn-loading btn-for-modal">${actionBtnText}</button>
                                    </div>
                                </form>
                            </div>
                        </div>`,
            },

        );
    }
    function openModalByContent(data = {
        content: "",
        animation: "mfp-move-horizontal",
        size: "medium",
    }) {
        $.magnificPopup.open({
            removalDelay: 500,
            items: {
                src: `<div class="white-popup mfp-with-anim ${data.size ?? "medium"}">${data.content}</div>`, // can be a HTML string, jQuery object, or CSS selector
            },
            callbacks: {
                beforeOpen: function () {
                    this.st.mainClass = data.animation ?? "mfp-move-horizontal";
                },
                open: function () {
                    var modalCloseBtn = this.contentContainer.find(".modal-close");
                    $(modalCloseBtn).click(function () {
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
</script>
@endpush
