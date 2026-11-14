@php
  $journals = $user->journals()->orderBy('id', 'DESC')->simplePaginate(20);
@endphp

<div class="card card-light">
  <div class="card-header text-secondary">@lang('Журнал логов')</div>
  <div class="card-body p-0">
    <table class="table table-striped table-empty">
      <thead>
        <tr>
          <th style="width: 60px;">№</th>
          <th>@lang('Событие')</th>
          <th style="width: 160px;">@lang('IP адрес')</th>
          <th style="width: 160px;">@lang('Дата')</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($journals as $journal)
          <tr>
            <td>{{ $loop->iteration + ($journals->currentPage() - 1) * $journals->perPage() }}</td>
            <td>
              {{ $journal->type_title }} &rarr;
              {{ $journal->event_title }}
              @if ($journal->entity)
                &rarr; {{ $journal->entity_title }} <span class="text-muted">[#{{ $journal->entity_id }}]</span>
              @endif
              @if ($short = $journal->short_description)
                &rarr; {{ $short }}
              @endif
            </td>
            <td>{{ $journal->ip_address }}</td>
            <td><x-form::datetime :value="$journal->created_at" /></td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <div class="float-right mt-3 mr-3">{{ $journals->links() }}</div>
  </div>
</div>
