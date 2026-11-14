<form method="POST" action="{{ route('admin.user.notification.sync', $user) }}" class="ajax-form">
  <div class="card card-light">
    <div class="card-header text-secondary">@lang('Уведомления')</div>

    <div @class(['card-body', 'table-danger' => $user->deleted_at])>
      <div class="form-row">
        <table class="table table-bordered table-empty">
          <thead>
            <tr>
              <th>@lang('Событие')</th>
              @foreach ($notificationChannels as $title)
                <th class="text-center" style="width: 100px;">{{ $title }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach (config('eloquent_notification.trigger') as $trigger => $details)
              <tr>
                <td>{{ trans($details['title']) }}</td>
                @foreach (array_keys($notificationChannels) as $channel)
                  <td class="text-center">
                    @if (in_array($channel, $details['channels']))
                      <x-form::toggle name="user_notifications[{{ $trigger }}][]" :value="$channel" :checked="in_array($channel, $user->userNotifications->where('trigger', '=', $trigger)->first()->channels ?? [])" />
                    @else
                      -
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer">
      <input type="submit" class="btn btn-outline-primary btn-sm" value="@lang('Сохранить')" />
    </div>
  </div>
</form>
