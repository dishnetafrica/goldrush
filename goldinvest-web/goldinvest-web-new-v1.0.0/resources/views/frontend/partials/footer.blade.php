<!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start footer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
@if(isset($footer->value))
<footer class="footer-section pt-60 pb-20 bg_img" data-background="{{ get_image(@$footer->value->image, 'site-section') }}">
    <div class="container">
        <div class="footer-wrapper">
            <div class="row mb-30-none">
                <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-sm-6 mb-30">
                    <div class="footer-widget">
                        <div class="footer-logo">
                            <a class="site-logo site-title" href="{{setRoute("frontend.index")}}"><img src="{{ get_logo($basic_settings,'dark') }}" alt="site-logo"></a>
                        </div>
                        <div class="footer-content">
                            <p>{{ $footer->value->contact->language->$lang->contact_desc ?? $footer->value->contact->language->$default->contact_desc ?? "" }}</p>
                        </div>
                        <div class="footer-content-bottom">
                            <ul class="footer-list logo">
                                <li><a href="#0"><i class="las la-phone-volume me-1"></i> {{ $footer->value->contact->phone }}</a></li>
                                <li><a href="#0"><i class="las la-envelope me-1"></i> {{ $footer->value->contact->email }}</a></li>
                                <li><a href="#0"><i class="las la-user me-1"></i> {{ $footer->value->contact->support }}</a></li>
                            </ul>
                        </div>
                        <div class="language-select language-switcher">
                            <select class="select2" name="lang_switcher" id="">
                                @foreach($__languages as $item)
                                    <option value="{{$item->code}}" @if (get_default_language_code() == $item->code) selected  @endif>{{$item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-2 col-xl-2 col-lg-2 col-md-6 col-sm-6 mb-30">
                    <div class="footer-widget">
                        <h4 class="widget-title">{{ __("Useful Links") }}</h4>
                        <ul class="footer-list">
                            @foreach($useful_links ?? [] as $key => $data)
                                <li><a href="{{ route('frontend.useful.links',$data->slug) }}">{{ @$data->title->language->$lang->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-xxl-2 col-xl-2 col-lg-2 col-md-6 col-sm-6 mb-30">
                    <div class="footer-widget">
                        <h4 class="widget-title">{{ __("Download App") }}</h4>
                        <p>{{ @$app_settings->url_title }}</p>
                        <ul class="footer-list two">
                            <li><a href="{{ $app_settings->android_url ?? '' }}" target="_blank" class="app-img"><img src="{{ asset('public/frontend/assets/images/app/play_store.webp') }}" alt="app"></a></li>
                            <li><a href="{{ $app_settings->iso_url ?? '' }}" target="_blank" class="app-img"><img src="{{ asset('public/frontend/assets/images/app/app_store.webp') }}" alt="app"></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-sm-6 mb-30">
                    <div class="footer-widget">
                        <h4 class="widget-title">{{ __("Newsletter") }}</h4>
                        <p>{{ @$footer->value->contact->language->$lang->contact_heading }}</p>
                        <ul class="footer-list two">
                            <form action="{{ setRoute('frontend.subscribe') }}" method="POST">
                                @csrf
                                <li>
                                    <input type="text" name="name" placeholder="{{ __("Name") }}" class="form--control">
                                    <span class="input-icon"><i class="las la-user"></i></span>
                                </li>
                                <li>
                                    <input type="email" name="email" placeholder="{{ __("Email") }}" class="form--control" required>
                                    <span class="input-icon"><i class="las la-envelope"></i></span>
                                </li>
                                <li>
                                    <button class="btn--base sub-btn" type="type">{{ __("Subscribe") }}<i class="las la-angle-right ms-1"></i></button>
                                </li>
                            </form>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="copyright-area">
                <div class="copyright-wrapper">
                    <p>&copy; {{ date('Y') }}  <span class="text--base"> <a href="{{ url('/') }}">{{ $basic_settings->site_name }}</a></span> . {{ $footer->value->contact->language->$lang->footer_text ?? $footer->value->contact->language->$default->footer_text ?? "" }}</p>
                    <ul class="footer-social-list">
                        @foreach($footer->value->contact->social_links ?? [] as $key => $item)
                        <li>
                            <a href="{{@$item->link }}" target="_blank"><i class="{{@$item->icon }}"></i></a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <a href="#" class="click-scroll">
                    <i class="las la-angle-up"></i>
                </a>
            </div>
        </div>
    </div>
</footer>
@endif
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End footer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@push('script')


<script>
    // JavaScript to dynamically insert the current year
    $("select[name=lang_switcher]").change(function(){
            var selected_value = $(this).val();
            var submitForm = `<form action="{{ setRoute('frontend.languages.switch') }}" id="local_submit" method="POST"> @csrf <input type="hidden" name="target" value="${$(this).val()}" ></form>`;
            $("body").append(submitForm);
            $("#local_submit").submit();
        });

</script>
@endpush
