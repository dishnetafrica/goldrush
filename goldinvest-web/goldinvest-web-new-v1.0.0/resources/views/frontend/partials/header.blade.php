@php
    $type = App\Constants\GlobalConst::SETUP_PAGE;
    $menus = DB::table('setup_pages')
            ->where('status', 1)
            ->get();
    $current_url = URL::current();
@endphp
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Header
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<header class="header-section">
    <div class="header">
        <div class="header-bottom-area">
            <div class="container">
                <div class="header-menu-content">
                    <nav class="navbar navbar-expand-lg p-0">
                        <a class="site-logo site-title" href="{{setRoute("frontend.index")}}"><img src="{{ get_logo($basic_settings,'dark') }}" alt="site-logo"></a>
                        <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                            aria-expanded="false" aria-label="Toggle navigation">
                            <span class="fas fa-bars"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav main-menu ms-auto me-auto">
                                @foreach($menus as $item)
                                    @if($item->slug == 'plan')
                                        <li>
                                            <a href="javascript:void(0)" class="@if ($current_url == url($item->url) || $current_url == url('/gold/store')) active @endif">
                                                <span>{{ __($item->title) }}</span> <i class="fas fa-angle-down"></i>
                                            </a>
                                            <ul class="sub-menu">
                                                <li><a href="{{ setRoute('frontend.plan') }}">{{ __("Gold Invest") }}</a></li>
                                                <li><a href="{{ setRoute('frontend.gold.store') }}">{{ __("Gold Store") }}</a></li>
                                            </ul>
                                        </li>
                                    @else
                                        <li>
                                            <a href="{{ url($item->url) }}" class="@if ($current_url == url($item->url)) active @endif">
                                                <span>{{ __($item->title) }}</span>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                            <div class="header-action">
                                @auth
                                    <a href="{{ setRoute('user.dashboard') }}" class="btn--base">
                                        {{ __('Dashboard') }}
                                    </a>
                                @else
                                    <a href="{{setRoute('user.login')}}" class="btn--base"><i class="las la-user-edit me-2"></i>{{ __("Login") }}</a>
                                @endauth
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</header>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Header
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
