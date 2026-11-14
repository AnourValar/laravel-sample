@extends('admin.layout')

@section('title')
  @lang('Создание справочника')
@endsection

@section('content')
  <div class="row">
    <div class="col-md-12 col-lg-8 mx-auto">
      <form method="POST" action="{{ route('admin.directory.create') }}" class="ajax-form">
        <div class="card card-light">
          <div class="card-header text-secondary">@lang('Карточка')</div>

          <div class="card-body">
            <div class="form-row">
              <div class="col-md-4">
                <label class="required">@lang('Тип')</label>
                <x-select
                  name="directory[type]"
                  :options="ConfigHelper::values('models.directory.type', fn($value, $key) => App::make(config('models.directory.type.' . $key . '.bind')) instanceof App\Handlers\Models\Directory\Type\RenderInterface)"
                  :prepends="['' => __('-- Выберите тип --')]"
                  class="form-control"
                  data-render-url="{{ route('admin.directory.render_type') }}"
                  data-render-target="#directory-data"
                  data-render-placeholder="1"
                />
              </div>

              <div class="col-md-4">
                <label>@lang('Пользователь')</label>
                <x-select
                  name="directory[user_id]"
                  class="form-control select2bs4-autocomplete"
                  data-url="{{ route('admin.user.autocomplete', ['operation' => 'is_customer']) }}"
                  :prepends="['' => '']"
                  :options="$directory->user_id ? [$directory->user] : []"
                  :selected="$directory->user_id"
                  :data-placeholder="__('Отсутсвует')"
                />
              </div>
            </div>
            <hr />
            <div id="directory-data" class="mt-3">
              <div class="col-md-12 font-italic text-center">&lt; @lang('Выберите тип') &gt;</div>
            </div>
          </div>

          <div class="card-footer">
            <input type="submit" class="btn btn-success" value="@lang('Создать')" />
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
