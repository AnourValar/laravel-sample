<div class="form-row">
  <div class="col-md-6">
    <label class="required">@lang('Название')</label>
    <x-input
      type="text"
      name="{{ $name }}[title]"
      :value="$directory->data['title'] ?? null"
      class="form-control"
      maxlength="40"
    />
  </div>

  <div class="col-md-2">
    <label class="required">@lang('Цвет')</label>
    <x-input
      type="color"
      name="{{ $name }}[color]"
      :value="$directory->data['color'] ?? ''"
      class="form-control"
    />
  </div>
</div>

<div class="form-row">
  <div class="col-md-12 mt-3">
    <label>@lang('Описание')</label>
    <x-textarea
      name="{{ $name }}[description]"
      class="form-control"
      :value="$directory->data['description'] ?? null"
      rows="3"
      maxlength="100"
    />
  </div>
</div>
