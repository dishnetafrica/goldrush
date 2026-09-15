<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start map section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<div class="map-section pb-120">
    @if(isset($overview->value))
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 text-center">
                <div class="section-header">
                    <span class="section-sub-titel"><i class="{{ @$overview->value->section_icon }}"></i> {{ @$overview->value->language->$lang->section_title ?? @$overview->value->language->$default->section_title }}</span>
                    <h2 class="section-title">{{ @$overview->value->language->$lang->heading ?? @$overview->value->language->$default->heading }}</h2>
                    <p>{{ @$overview->value->language->$lang->description ??  @$overview->value->language->$default->description }}</p>
                </div>
            </div>
        </div>
        <div class="map-wrapper">
            <div id="world-map-markers"></div>
        </div>
        <div class="map-content">
            <div class="map-statistics-wrapper">
                @forelse($overview->value->items ?? [] as $key => $item)
                <div class="statistics-item">
                    <div class="statistics-content">
                        <div class="odo-area">
                            <h3 class="odo-title odometer" data-odometer-final="{{ @$item->item_no }}">0</h3>
                            <h3 class="title">+</h3>
                        </div>
                        <p>{{ @$item->langugae->$lang->title ?? @$item->langugae->$default->title }}</p>
                    </div>
                </div>
                @empty
                    @include('admin.components.alerts.empty',['colspan' => 7])
                @endforelse
            </div>
            <div class="content-bottom">
                <p>{{ @$overview->value->language->$lang->sub_heading ?? @$overview->value->language->$default->sub_heading }}</p>
                <a href="{{ url(@$overview->value->button_link) }}">{{ @$overview->value->language->$lang->button_name ?? @$overview->value->language->$default->button_name }} <i class="{{ @$overview->value->button_icon }}"></i></a>
            </div>
        </div>
    </div>
    @endif
</div>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End map section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
