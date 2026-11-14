<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>
    @hasSection('title')
      @yield('title')
    @else
      {{ config('app.name') }}
    @endif
  </title>
  <meta name="robots" content="noindex, nofollow" />
  <link
    rel="icon"
    type="image/png"
    sizes="16x16"
    href="{{ url('static-admin/favicon.png') }}"
  />
  @vite(['resources/css/admin_vendor.css', 'resources/css/admin_app.css'])
  @stack('head')
</head>

<body class="hold-transition layout-top-nav">
  @vite(['resources/js/admin_vendor.js', 'resources/js/admin_app.js'])
  <script type="module">
    scaffolding.i18n = {!! json_encode(array_replace_recursive(trans('package', [], config('app.fallback_locale')), trans('package')), JSON_UNESCAPED_UNICODE) !!};
    scaffolding.errors = @json($errors->toArray());
    scaffolding.utc_offset = @json(now()->setTimeZone(config('atom.timezone_client'))->format('O'));
    scaffolding.user_id = {{ Auth::id() }};
  </script>
  <div class="wrapper">

    {{-- #public_bundle --}}
    <div class="progress progress-xs fixed-top d-none" id="progress-bar" style="z-index: 9000; height: 6.99px;">
      <div
        class="progress-bar bg-info progress-bar-primary progress-bar-striped"
        role="progressbar"
        aria-valuenow="40"
        aria-valuemin="0"
        aria-valuemax="200"
        style="width: 40%"
      ></div>
    </div>
    <div
      class="spinner-grow text-info position-fixed d-none"
      id="progress-bar-spinner"
      role="status"
      style="z-index: 9000; right: 15px; top: 15px; width: 3rem; height: 3rem;"
    >
      <span class="sr-only"></span>
    </div>

    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white navbar-fixed">
      <div class="container-fluid">
        <a href="{{ route('admin.index') }}" class="navbar-brand">
          <img src="{{ url('static-admin/favicon.png') }}" class="brand-image" style="opacity: .8">
          {{-- <span class="brand-text font-weight-light">{{ config('app.name') }}</span> --}}
        </a>
        <button
          class="navbar-toggler order-1"
          type="button"
          data-toggle="collapse"
          data-target="#navbarCollapse"
          aria-controls="navbarCollapse"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse order-3" id="navbarCollapse">

          <ul class="navbar-nav">
            @include('admin.layout_topmenu')
          </ul>
        </div>

        <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
          @if (count(config('models.user.locale')) > 1)
            <li class="nav-item dropdown">
              <a
                href="#"
                id="dropdownLocale"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                class="nav-link dropdown-toggle pr-1"
                title="@lang('Язык')"
              >
                <span class="flag-icon {{ config('models.user.locale.' . config('app.locale') . '.icon') }}"></span>
              </a>
              <ul aria-labelledby="dropdownLocale" class="dropdown-menu dropdown-menu-right border-0 shadow">
                @foreach (config('models.user.locale') as $localeKey => $locale)
                  <li>
                    <a href="{{ request()->fullUrlWithQuery(['locale' => $localeKey]) }}" class="dropdown-item {{ config('app.locale') === $localeKey ? 'active' : '' }}">
                      <span class="flag-icon {{ $locale['icon'] }}"></span>
                      {{ $locale['title'] }}
                    </a>
                  </li>
                @endforeach
              </ul>
            </li>
          @endif
          <li class="nav-item">
            <span class="nav-link font-italic pr-0 pt-2 mt-1 text-sm">{{ Auth::user()->title }}</span>
          </li>
          <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <input type="submit" class="btn btn-link nav-link text-dark pl-2" value="[ @lang('Выход') ]" />
            </form>
          </li>
        </ul>
      </div>
    </nav>

    <div class="content-wrapper pb-1" style="margin-top: 57px;">
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            @hasSection('breadcrumbs')
              <div class="col-sm-12">
                <ol class="breadcrumb pl-1">
                  @yield('breadcrumbs')
                </ol>
              </div>
            @else
              <div class="col-sm-12">
                <h1 class="m-0 text-dark" style="font-size: 22px;">@yield('title')</h1>
              </div>
            @endif
          </div>
        </div>
      </div>
      <div class="content">
        @stack('precontent')
        @include('admin.layout_message')
        <div class="container-fluid" id="vue-root">
          @yield('content')
          @stack('appendcontent')
        </div>
        @stack('postcontent')
        <form id="form-simple" method="POST">@csrf</form>
        <form id="form-ajax" method="POST" class="ajax-form">@stack('form-ajax')</form>
      </div>
    </div>

    <footer class="main-footer">
      <div class="float-right d-none d-sm-inline"></div>
      {{-- prettier-ignore-start --}}
      <span>{{ config('app.name') }}@hasSection('title')| @yield('title')@endif</span>
      {{-- prettier-ignore-end --}}
    </footer>

  </div>
</body>

</html>
