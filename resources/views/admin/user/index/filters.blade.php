@inject('dateHelper', App\Helpers\DateHelper::class)
<x-form::filters :request="$request" route="admin.user.index">
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
              'focus' => !$request->filter('search.search') || !count($users),
          ])
          autocomplete="off"
          placeholder="{{ __('Поиск...') }}"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Роль')</label>
        <x-select
          name="filter[role][in][]"
          options="models.user.role"
          :selected="$request->filter('role.in', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбрана') }}"
        />
      </div>
    </div>

    <div class="col-4">
      <div class="form-group">
        <label>@lang('Теги пользователя')</label>
        <x-select
          name="filter[tag_ids][json-in][]"
          :options="$userTags"
          :mapping="['value' => 'id']"
          :selected="$request->filter('tag_ids.json-in', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбраны') }}"
        />
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-2">
      <div class="form-group">
        <label>@lang('Создан, с')</label>
        <x-input
          type="text"
          name="filter[created_at][>=]"
          :value="$dateHelper->formatDate($request->filter('created_at.>='))"
          class="form-control date-picker from"
          autocomplete="off"
          placeholder="{{ __('Не выбрано') }}"
        />
      </div>
    </div>

    <div class="col-2">
      <div class="form-group">
        <label>@lang('Создан, по')</label>
        <x-input
          type="text"
          name="filter[created_at][<=]"
          :value="$dateHelper->formatDate($request->filter('created_at.<='))"
          class="form-control date-picker to"
          autocomplete="off"
          placeholder="{{ __('Не выбрано') }}"
        />
      </div>
    </div>

    <div class="col-2">
      <div class="form-group">
        <label>@lang('Удалён')</label>
        <x-select
          name="filter[deleted_at][is-null][]"
          :options="(array) trans('form.boolean_reverse')"
          :selected="$request->filter('deleted_at.is-null', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбрано') }}"
        />
      </div>
    </div>
  </div>
</x-form::filters>
