<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start app section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    @if(isset($app->value))
    <div class="container">
        <div class="row mb-30-none justify-content-center align-items-center">
            <div class="col-xxl-2 col-xl-2 col-lg-1 d-md-none"></div>
            <div class="col-xxl-4 col-xl-4 col-lg-5 col-md-6 mb-30">
                <div class="thumb">
                    <img src="{{ get_image(@$app->value->image, 'site-section') }}" alt="img">
                </div>
            </div>
            <div class="col-xxl-1 col-xl-1 col-lg-1 d-md-none"></div>
            <div class="col-xxl-5 col-xl-5 col-lg-5 col-md-6 mb-30">
                <div class="content text-sm-center">
                    <h2 class="display-2 fw-bolder mb-10">{{ @$app->value->language->$lang->title ?? @$app->value->language->$default->title }}</h2>
                    <p>{!! @$app->value->language->$lang->description ?? @$app->value->language->$default->description !!}</p>
                    <div class="download-btn-area align-items-center d-flex justify-content-sm-center pt-20 m-8-none">
                            <a href="{{ $app_settings->iso_url ?? '' }}" target="_blank" class="m-8"><img src="{{ asset('public/frontend/assets/images/app/app_store.webp') }}" alt="img"></a>
                            <a href="{{ $app_settings->android_url ?? '' }}" target="_blank" class="m-8"><img src="{{ asset('public/frontend/assets/images/app/play_store.webp') }}" alt="img"></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End app section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

