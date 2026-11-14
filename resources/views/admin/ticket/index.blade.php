@extends('admin.layout')

@section('title')
  @lang('Список тикетов')
@endsection

@section('content')
  @include('admin.ticket.index.filters')

  <x-form::table-page :request="$request" :collection="$tickets">
    <table class="table table-bordered table-striped table-empty">
      <thead>
        <tr>
          <th style="width: 75px"># <x-form::sort :request="$request" attribute="id" /></th>
          <th>@lang('Тип')</th>
          <th style="width: 300px;">@lang('Автор')</th>
          <th style="width: 140px;">@lang('Статус') </th>
          <th style="width: 300px;">@lang('Модератор')</th>
          <th style="width: 150px;">@lang('Создан')</th>
          <th style="width: 150px;">@lang('Закрыт') <x-form::sort :request="$request" attribute="closed_at" /></th>
          <th style="width: 85px;"></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($tickets as $ticket)
          <tr @class(['table-secondary' => $ticket->closed_at])>
            <td><x-form::link :value="$ticket" entity="ticket" class="text-bold focus" /></td>
            <td>{{ $ticket->type_title }}</td>
            <td>
              <x-admin.user
                :value="$ticket->author"
                display="title"
                default="-"
                target="_blank"
              />
            </td>
            <td><span class="badge badge-{{ $ticket->status_details['ui'] }}">{{ $ticket->status_title }}</span></td>
            <td><x-admin.user
                :value="$ticket->moderator"
                display="title"
                target="_blank"
                default="-"
              /></td>
            <td><x-form::datetime :value="$ticket->created_at" /></td>
            <td><x-form::datetime :value="$ticket->closed_at" default="-" /></td>
            <td class="text-center align-middle pt-2 pb-2 pl-0 pr-0">
              <x-form::link_button :value="$ticket" entity="ticket" />
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </x-form::table-page>
@endsection
