@extends('admin.layout')

@section('title')
  @lang('Список справочников')
@endsection

@section('content')
  @include('admin.directory.index.filters')

  <x-form::table-page :request="$request" :collection="$directories">
    <table class="table table-bordered table-striped table-empty">
      <thead>
        <tr>
          <th style="width: 75px"># <x-form::sort :request="$request" attribute="id" /></th>
          <th style="width: 220px;">@lang('Тип')</th>
          <th>@lang('Значение')</th>
          <th style="width: 350px;">@lang('Пользователь') <x-form::sort :request="$request" attribute="user_id" /></th>
          <th style="width: 85px;"></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($directories as $directory)
          <tr>
            <td><x-form::link :value="$directory" entity="directory" class="text-bold focus" /></td>
            <td>{{ $directory->type_title }}</td>
            <td>
              @if ($directory->getTypeHandler() instanceof App\Handlers\Models\Directory\Type\RenderInterface)
                {!! $directory->getTypeHandler()->renderShow($directory) !!}
              @else
                <span class="text-secondary font-italic">
                  <x-form::str_limit :string="json_encode($directory->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)" limit="150" />
                </span>
              @endif
            </td>
            <td>
              <x-admin.user
                :value="$directory->user"
                display="title"
                default="-"
                target="_blank"
              />
            </td>
            <td class="text-center align-middle pt-2 pb-2 pl-0 pr-0">
              <x-form::link_button :value="$directory" entity="directory" />
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </x-form::table-page>
@endsection
