@extends('admin.layout')

@section('title')
  @lang('Тикет'): #{{ $ticket->id }}
@endsection

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.ticket.index') }}">@lang('Список тикетов')</a></li>
  <li class="breadcrumb-item active">#{{ $ticket->id }}</li>
@endsection

@section('content')
  <div class="row">
    <div class="col-md-12 col-lg-8 mx-auto">
      @include('admin.ticket.form.base')

      @include('admin.ticket.form.data_request')
    </div>

    <div class="col-md-12 col-lg-4">
      @include('admin.ticket.form.overview')

      @include('admin.ticket.form.attachments')
    </div>
  </div>
@endsection
