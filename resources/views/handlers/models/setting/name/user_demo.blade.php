<div class="form-row">
  <div class="col-md-4">
    <label>@lang('Телефон')</label>
    <x-input
      type="text"
      :name="$inputName . '[phone]'"
      :value="$value['phone']"
      class="form-control phone-mask"
      autocomplete="off"
      required
    />
  </div>

  <div class="col-md-3">
    <label>@lang('СМС Код')</label>
    <x-input
      type="text"
      :name="$inputName . '[code]'"
      :value="$value['code']"
      class="form-control"
      autocomplete="off"
      data-inputmask-mask="999999"
      required
    />
  </div>

  <div class="col-md-3">
    <label>@lang('Включён')</label>
    <div><x-form::toggle
        class="null-if-empty"
        :name="$inputName . '[enabled]'"
        :value="1"
        :checked="$value['enabled']"
      /></div>
  </div>
</div>
