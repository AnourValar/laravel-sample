@inject('dateHelper', App\Helpers\DateHelper::class)
<x-form::filters :request="$request" route="admin.ticket.index">
  <div class="row">
    <div class="col-3">
      <div class="form-group">
        <label>@lang('Автор')</label>
        <x-select
          name="filter[author_id][in][]"
          :options="$request['authors']"
          :selected="$request->filter('author_id.in', [])"
          :prepends="[' ' => __('-- Отсутствует --')]"
          class="select2bs4-autocomplete"
          multiple="multiple"
          data-url="{{ route('admin.user.autocomplete') }}?operation=is_customer"
          data-placeholder="{{ __('Не выбран') }}"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Модератор')</label>
        <x-select
          name="filter[moderator_id][in][]"
          :options="$request['moderators']"
          :selected="$request->filter('moderator_id.in', [])"
          :prepends="[' ' => __('-- Отсутствует --')]"
          class="select2bs4-autocomplete"
          multiple="multiple"
          data-url="{{ route('admin.user.autocomplete') }}?operation=is_staff"
          data-placeholder="{{ __('Не выбран') }}"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Тип')</label>
        <x-select
          name="filter[type][in][]"
          options="models.ticket.type"
          :selected="$request->filter('type.in', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбран') }}"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Статус')</label>
        <x-select
          name="filter[status][in][]"
          options="models.ticket.status"
          :selected="$request->filter('status.in', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбран') }}"
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
          :value="$dateHelper->formatDatetime($request->filter('created_at.>='))"
          class="form-control datetime-picker"
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
          :value="$dateHelper->formatDatetime($request->filter('created_at.<='))"
          class="form-control datetime-picker"
          autocomplete="off"
          placeholder="{{ __('Не выбрано') }}"
        />
      </div>
    </div>
  </div>
</x-form::filters>
