@extends('admin.layout')

@section('title')
  @lang('Пользователь'): {{ $user->title }}
@endsection

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.user.index') }}">@lang('Список пользователей')</a></li>
  <li class="breadcrumb-item active">{{ $user->title }}</li>
@endsection

@section('button_primary')
  <input type="submit" class="btn btn-primary" value="@lang('Сохранить')" />
@endsection

@section('button_additional')
  @if (Auth::user()->can('admin.user.delete'))
    @if ($user->deleted_at)
      <input
        type="submit"
        class="btn btn-warning swal-confirm"
        value="@lang('Восстановить!')"
        formaction="{{ route('admin.user.restore', $user) }}"
      />
    @else
      <input
        type="submit"
        class="btn btn-danger swal-confirm"
        value="@lang('Удалить!')"
        formaction="{{ route('admin.user.delete', $user) }}"
      />
    @endif
  @endif

  @if (Auth::user()->can('admin.user.login_as') && Auth::user()->id != $user->id && !$user->deleted_at && $user->getRoleHandler() instanceof App\Handlers\Models\User\Role\OnlineInterface)
    <div class="float-right">
      <input
        type="submit"
        class="btn btn-outline-dark swal-confirm"
        value="@lang('Войти под пользователем')"
        formaction="{{ route('admin.user.login_as', $user) }}"
        form="form-simple"
      />
    </div>
  @endif
@endsection

@section('content')
  <x-form::qr :value="Session::get('totp_secret_url')">
    <x-slot name="title">
      <b>@lang('Ключ:')</b>
      {{ Session::get('totp_secret') }}
      <hr />
    </x-slot>
  </x-form::qr>

  <div class="row">
    <div class="col-md-12 col-lg-8 mx-auto">
      @include('admin.user.form.base')

      <div id="user-role-data">
        @if ($user->getRoleHandler() instanceof App\Handlers\Models\User\Role\FormInterface)
          {!! $user->getRoleHandler()->renderEdit($user, 'user') !!}
        @endif
      </div>

      @include('admin.user.form.fields')

      @include('admin.user.form.history')
    </div>

    <div class="col-md-12 col-lg-4">
      @include('admin.user.form.overview')

      @include('admin.user.form.notifications')

      @if ($user->email || $user->phone)
        @include('admin.user.form.abilities')
      @endif
    </div>
  </div>
@endsection
