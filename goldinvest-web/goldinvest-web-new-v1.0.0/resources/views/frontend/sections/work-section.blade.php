<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start work section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<div class="work-section ptb-120">
    @if(isset($work->value))
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 text-center">
                <div class="section-header">
                    <span class="section-sub-titel"><i class="{{ @$work->value->section_icon }}"></i>  {{ @$work->value->language->$lang->heading ?? @$work->value->language->$default->heading }}</span>
                    <h2 class="section-title">{{ @$work->value->language->$lang->sub_heading ?? @$work->value->language->$default->sub_heading  }}</h2>
                    <p>{{ @$work->value->language->$lang->description ?? @$work->value->language->$default->description }}</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center mb-30-none">
            @forelse ($work->value->items ?? []  as $key => $item )
            <div class="col-lg-4 col-md-4 col-sm-6 mb-30">
                <div class="work-item">
                    <div class="thumb-area">
                        <img src="{{ get_image(@$item->image, 'site-section') }}" alt="statistics-item">
                    </div>
                    <div class="content">
                        <h4>{{ @$item->language->$lang->title ?? @$item->language->$default->title }}</h4>
                        <p>{{ @$item->language->$lang->item_description ?? @$item->language->$default->item_description }}</p>
                    </div>
                </div>
            </div>
            @empty
                @include('user.components.alerts.empty',['colspan' => 7])
            @endforelse
        </div>
    </div>
    @endif
</div>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End work section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

