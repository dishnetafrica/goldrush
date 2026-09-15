<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start why choose us section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="choose-us-section">
    @if(isset($why_choose_us->value))
    <div class="container">
        <div class="choose-us-main-wrapper">
            <div class="row justify-content-center">
                <div class="col-xl-8 text-center">
                    <div class="section-header">
                        <span class="section-sub-titel"><i class="{{@$why_choose_us->value->section_icon}}"></i> {{ @$why_choose_us->value->language->$lang->heading ?? @$why_choose_us->value->language->$default->heading }}</span>
                        <h2 class="section-title">{{ @$why_choose_us->value->language->$lang->sub_heading ?? @$why_choose_us->value->language->$default->sub_heading  }}</h2>
                        <p>{{ @$why_choose_us->value->language->$lang->description ?? @$why_choose_us->value->language->$default->description }}</p>
                    </div>
                </div>
                <div class="row mb-30-none justify-content-center">
                    @forelse($why_choose_us->value->items ?? [] as $key => $item)
                    <div class="col-lg-4 col-md-6 mb-30">
                        <div class="choose-us-item">
                            <div class="icon-wrapper">
                                <div class="icon-area">
                                    <i class="{{ @$item->icon }}"></i>
                                    <span class="choose-badge">{{ @$item->item_no }}</span>
                                </div>
                            </div>
                            <h3 class="title">{{ @$item->language->$lang->title ?? @$item->language->$default->title }}</h3>
                            <p>{{ @$item->language->$lang->item_description ?? @$item->language->$default->item_description }}</p>
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
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End why choose us section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
