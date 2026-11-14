@extends('admin.layout')

@section('title')
  @lang('Справочник'): #{{ $directory->id }}
@endsection

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.directory.index') }}">@lang('Список справочников')</a></li>
  <li class="breadcrumb-item active">#{{ $directory->id }}</li>
@endsection

@section('content')
  <div class="row">
    <div class="col-md-12 col-lg-8 mx-auto">
      @include('admin.directory.form.base')
    </div>

    <div class="col-md-12 col-lg-4">
      @include('admin.directory.form.overview')
    </div>
  </div>
@endsection
