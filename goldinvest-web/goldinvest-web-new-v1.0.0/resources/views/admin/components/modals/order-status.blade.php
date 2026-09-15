@if (admin_permission_by_name("admin.order.status"))
  <div id="status-change" class="mfp-hide large">
      <div class="modal-data">
          <div class="modal-header px-0">
              <h5 class="modal-title">{{ __("Update Status") }}</h5>
          </div>
          <div class="modal-form-data">
              <form class="card-form" action="{{ setRoute('admin.order.status') }}" method="POST">
                  @csrf
                  <input type="hidden" name="target" value="{{ old('target') }}">
                  <div class="row mb-10-none">
                      <div class="col-xl-12 col-lg-12 form-group">
                        <select class="form-control" name="order_status"  id="order_status">
                            <option disabled selected value="">{{ __("Select Order Status") }}</option>
                            <option value="{{ global_const()::ACCEPTED }}">{{ __("Accepted") }}</option>
                            <option value="{{ global_const()::ONGOING}}">{{ __("Ongoing") }}</option>
                            <option value="{{ global_const()::DELIVERED}}">{{ __("Delivered") }}</option>
                            <option value="{{ global_const()::CANCELLED}}">{{ __("Cancelled") }}</option>
                        </select>
                        <div id="cancel_reason_container" style="display: none;">
                            <textarea class="form-control mt-3" name="cancel_reason" placeholder="{{ __('Enter cancellation reason (optional)') }}"></textarea>
                        </div>
                      </div>
                      <div class="col-xl-12 col-lg-12 form-group">
                          @include('admin.components.button.form-btn',[
                              'class'         => "w-100 btn-loading",
                              'text'          => __("Submit"),
                          ])
                      </div>
                  </div>
              </form>
          </div>
      </div>
  </div>

@push('script')
<script>
     openModalWhenError("status_change","#status-change");
     $("#order_status").on('change', function() {
        if ($(this).val() == "{{ global_const()::CANCELLED }}") {
            $("#cancel_reason_container").show();
        } else {
            $("#cancel_reason_container").hide();
        }
    });
    $(".status-button").click(function(){
        var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
        console.log(oldData);

        $("#status-change").find("input[name=target]").val(oldData.id);
        $("#status-change").find("select[name=order_status]").val(oldData.order_status);
        openModalBySelector("#status-change");
        setTimeout(() => {
            $("#status-change").find("select[name=order_status]").select2();
        }, 300);
    });
</script>
@endpush
@endif
