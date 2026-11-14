@extends('admin.layout')

@section('title')
  @lang('Создание пользователя')
@endsection

@section('breadcrumbs')
@endsection

@section('button_primary')
  <input type="submit" class="btn btn-success" value="@lang('Создать')" />
@endsection

@section('button_additional')
@endsection

@section('content')
  <div class="row">
    <div class="col-md-12 col-lg-8 mx-auto">
      @include('admin.user.form.base')

      <div id="user-role-data"></div>
    </div>
  </div>
@endsection
