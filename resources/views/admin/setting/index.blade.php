@extends('admin.layout')

@section('title')
  @lang('Настройки')
@endsection

@section('content')
  @foreach ($settings as $name => $value)
    <div class="row">
      <div class="col-md-12 col-lg-8 mx-auto">
        <form method="POST" action="{{ route('admin.setting.update') }}" class="ajax-form">
          <div class="card card-light">
            <div class="card-header text-secondary">
              {{ trans(config("models.setting.name.{$name}.title")) }}
            </div>

            <div class="card-body">
              {!! App::make(config("models.setting.name.{$name}.bind"))->renderEdit("settings[{$name}]", $value) !!}
            </div>

            <div class="card-footer">
              @if (Auth::user()->can('admin.setting.update'))
                <input type="submit" class="btn btn-primary" value="@lang('Сохранить')" />
              @endif
            </div>
          </div>
        </form>
      </div>
    </div>
  @endforeach
@endsection
