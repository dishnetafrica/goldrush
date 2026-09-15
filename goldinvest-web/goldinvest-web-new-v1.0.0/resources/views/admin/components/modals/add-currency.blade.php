@if (admin_permission_by_name("admin.currency.store"))
    <div id="currency-add" class="mfp-hide large">
        <div class="modal-data">
            <div class="modal-header px-0">
                <h5 class="modal-title">{{ __("Add Currency") }}</h5>
            </div>
            <div class="modal-form-data">
                <form class="modal-form" method="POST" action="{{ setRoute('admin.currency.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row mb-10-none">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label for="countryFlag">{{ __("Country Flag") }}</label>
                            <div class="col-12 col-sm-3 m-auto">
                                @include('admin.components.form.input-file',[
                                    'label'         => false,
                                    'class'         => "file-holder m-auto",
                                    'name'          => "flag",
                                ])
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            @include('admin.components.form.switcher',[
                                'label'         =>__('Type'),
                                'label_after'   =>"*",
                                'value'         => old('type','FIAT'),
                                'options'       => [__('FIAT') => 'FIAT',__('CRYPTO') => 'CRYPTO'],
                            ])
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label>{{ __("Country*") }}</label>
                            <select name="country" class="form--control select2-auto-tokenize country-select" data-old="{{ old('country') }}">
                                <option selected disabled>{{__('Select Country')}}</option>
                            </select>
                        </div>
                        <div class="col-xl-6 col-lg-6 form-group">
                            @include('admin.components.form.input',[
                                'label'         => __('Name'),
                                'label_after'   =>"*",
                                'name'          => 'name',
                                'value'         => old('name')
                            ])
                        </div>
                        <div class="col-xl-3 col-lg-3 form-group">
                            @include('admin.components.form.input',[
                                'label'         =>__('Code'),
                                'label_after'   =>"*",
                                'name'          => 'code',
                                'value'         => old('code')
                            ])
                        </div>
                        <div class="col-xl-3 col-lg-3 form-group">
                            @include('admin.components.form.input',[
                                'label'         => __('Symbol'),
                                'label_after'   =>"*",
                                'name'          => 'symbol',
                                'value'         => old('symbol')
                            ])
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group d-flex align-items-center justify-content-between mt-4">
                            <button type="button" class="btn btn--danger modal-close">{{ __("Cancel") }}</button>
                            <button type="submit" class="btn btn--base">{{ __("Add") }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push("script")
        <script>
            $(document).ready(function(){
                openModalWhenError("currency_add","#currency-add");
            });
        </script>
    @endpush
@endif
