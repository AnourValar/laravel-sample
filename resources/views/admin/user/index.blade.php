@extends('admin.layout')

@section('title')
  @lang('Список пользователей')
@endsection

@section('content')
  @include('admin.user.index.filters')

  <x-form::table-page :request="$request" :collection="$users">
    <table class="table table-bordered table-striped table-empty">
      <thead>
        <tr>
          <th style="width: 75px"># <x-form::sort :request="$request" attribute="id" /></th>
          <th>@lang('ФИО')</th>
          <th style="width: 200px;">@lang('Роль')</th>
          <th style="width: 160px;">@lang('Телефон')</th>
          <th style="width: 220px;">@lang('Email')</th>
          <th style="width: 300px;">@lang('Теги пользователя')</th>
          <th style="width: 100px;">@lang('Создан')</th>
          <th style="width: 120px;">@lang('Удалён') <x-form::sort :request="$request" attribute="deleted_at" /></th>
          <th style="width: 85px;"></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($users as $user)
          <tr @class(['table-danger' => $user->deleted_at])>
            <td>
              <x-admin.user :value="$user" class="text-bold focus" />
            </td>
            <td>{{ $user->title }}</td>
            <td>{{ $user->role_title }}</td>
            <td>{{ $user->phone_format }}</td>
            <td>{{ $user->email }}</td>
            <td>
              @forelse ((array) $user->tag_ids as $tagId)
                <span class="text-nowrap mr-2">{!! $userTags[$tagId]['title'] !!}</span>
              @empty
                -
              @endforelse
            </td>
            <td><x-form::date :value="$user->created_at" /></td>
            <td><x-form::date :value="$user->deleted_at" /></td>
            <td class="text-center align-middle pt-2 pb-2 pl-0 pr-0"><x-admin.user_button :value="$user" /></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </x-form::table-page>
@endsection
