<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@lang('Форма входа')</title>

  <meta name="robots" content="noindex, nofollow" />
  {{-- prettier-ignore --}}
  <link rel="icon" type="image/png" sizes="16x16" href="{{ url('static-admin/favicon.png') }}" />
  @vite(['resources/css/admin_vendor.css', 'resources/css/admin_app.css'])
</head>

<body class="hold-transition login-page">
  @vite(['resources/js/admin_vendor.js', 'resources/js/admin_app.js'])
  <script type="module">
    scaffolding.i18n = {!! json_encode(array_replace_recursive(trans('package', [], config('app.fallback_locale')), trans('package')), JSON_UNESCAPED_UNICODE) !!};
    scaffolding.errors = @json($errors->toArray());
    scaffolding.utc_offset = @json(now()->setTimeZone(config('atom.timezone_client'))->format('O'));
  </script>
  <div class="login-box">
    <div id="errors-list-template" class="d-none">
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0"></ul>
        <button
          type="button"
          class="close"
          data-dismiss="alert"
          aria-label="Close"
          onclick="unmark_errors()"
        ><span aria-hidden="true">&times;</span></button>
      </div>
    </div>

    <div class="card card-outline card-primary">
      <div class="card-header text-center pb-0">
        <h2>
          <a href="/"><img src="{{ url('static-admin/favicon.png') }}" class="brand-image img-cifrcle elevatfion-3" style="height: 50px;"></a>
          <b style="font-size: 30px;">{{ config('app.name') }}</b>
          <img src="{{ url('static-admin/favicon.png') }}" class="brand-image img-cifrcle elevatfion-3" style="height: 50px; opacity: 0;">
        </h2>
      </div>
      <div class="card-body">
        <p class="login-box-msg">@lang('Форма входа')</p>
        <form method="post">
          @csrf
          <div class="input-group mb-3">
            <x-input
              type="text"
              name="email"
              value=""
              class="form-control focus"
              :placeholder="__('Логин')"
              data-qa-id="login"
              required
            />
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-user"></span>
              </div>
            </div>
          </div>
          <div class="input-group mb-3">
            <input
              type="password"
              name="password"
              class="form-control"
              placeholder="@lang('Пароль')"
              data-qa-id="password"
              required
            />
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>
          <div class="input-group mb-3">
            <input
              type="text"
              name="code_totp"
              class="form-control"
              autocomplete="off"
              placeholder="@lang('Одноразовый код')"
            />
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-key"></span>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-8">
              <div class="icheck-primary">
                <x-input
                  type="checkbox"
                  name="remember"
                  id="remember"
                  :checked="1"
                />
                <label for="remember">@lang('Запомнить')</label>
              </div>
            </div>
            <div class="col-4">
              <button type="submit" class="btn btn-primary btn-block" data-qa-id="send-btn">@lang('Войти')</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>

</html>
