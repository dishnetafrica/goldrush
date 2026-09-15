<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Banner Section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@if(isset($banner->value))

<div class="banner-section bg_img" data-background="{{ get_image(@$banner->value->bg_image, 'site-section') }}">
    <div class="container">
        <div class="row align-items-center mb-30-none">
            <div class="col-lg-5 mb-30">
                <div class="banner-content">
                    <h1 class="title">{{ @$banner->value->language->$lang->heading ?? @$banner->value->language->$default->heading }}</h1>
                    <p class="sub-title">{{ @$banner->value->language->$lang->sub_heading ??  @$banner->value->language->$default->sub_heading }}</p>
                    <div class="banner-btn-area pt-20">
                        <a href="{{ url(@$banner->value->button_link) }}" class="btn--base">{{ @$banner->value->language->$lang->button_name ?? @$banner->value->language->$default->button_name }}<i class={{ @$banner->value->button_icon }}></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 mb-30">
                <div class="banner-thumb ps-lg-5">
                    <img src="{{ get_image(@$banner->value->image, 'site-section') }}" alt="Banner Image">
                </div>
            </div>
        </div>
        <div class="banner-bottom-wrapper">
            <div class="row justify-content-center mb-30-none">
                @forelse($banner->value->items ?? [] as $key => $item)
                <div class="col-lg-4 mb-30">
                    <div class="banner-item">
                        <div class="icon-area">
                            <img src="{{ get_image(@$item->image, 'site-section') }}" alt="icon">
                        </div>
                        <div class="content">
                            <h4 class="title">{{ @$item->language->$lang->title ?? @$item->language->$default->title }}</h4>
                            <p>{{ @$item->language->$lang->description ?? @$item->language->$default->description }}</p>
                        </div>
                    </div>
                </div>
                @empty
                    @include('admin.components.alerts.empty',['colspan' => 7])
                @endforelse
            </div>
        </div>
    </div>
</div>
@endif
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Banner Section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
