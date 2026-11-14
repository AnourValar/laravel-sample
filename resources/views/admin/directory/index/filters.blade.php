<x-form::filters :request="$request" route="admin.directory.index">
  <div class="row">
    <div class="col-3">
      <div class="form-group">
        <label>&nbsp;</label>
        <x-input
          type="text"
          name="filter[search][search]"
          :value="$request->filter('search.search')"
          @class([
              'form-control',
              'focus' => !$request->filter('search.search'),
          ])
          autocomplete="off"
          placeholder="{{ __('Поиск...') }}"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Тип')</label>
        <x-select
          name="filter[type][in][]"
          options="models.directory.type"
          :selected="$request->filter('type.in', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбран') }}"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Пользователь')</label>
        <x-select
          name="filter[user_id][in][]"
          :options="$request['users']"
          :selected="$request->filter('user_id.in', [])"
          :prepends="[' ' => __('-- Отсутствует --')]"
          class="select2bs4-autocomplete"
          multiple="multiple"
          data-url="{{ route('admin.user.autocomplete') }}?operation=is_customer"
          data-placeholder="{{ __('Не выбран') }}"
        />
      </div>
    </div>
  </div>
</x-form::filters>
