{{-- #public_bundle --}}
@php
  $mapping = [
      'success' => 'success',
      'warning' => 'warning',
      'primary' => 'info',
      'info' => 'info',
      'dark' => 'info',
      'secondary' => 'info',
  ];
@endphp
<script type="module">
  $(document).ready(function() {
    @foreach (Session::get('alert', []) as $key => $messages)
      @foreach ((array) $messages as $message)
        toastr.{{ $mapping[$key] }}(@json($message)).css({
          "width": "520px",
          "max-width": "520px"
        });
      @endforeach
    @endforeach
  });
</script>

@foreach (Session::get('link', []) as $key => $link)
  <div class="alert alert-light alert-dismissible fade show alert-blocks fade-in" role="alert">
    <div>
      <a href="{{ $link['href'] }}" class="btn btn-{{ $key }} text-decoration-none">{{ $link['title'] }}</a>
    </div>
    <button
      type="button"
      class="close"
      data-dismiss="alert"
      aria-label="Close"
    ><span aria-hidden="true">&times;</span></button>
  </div>
@endforeach

{{--
@foreach (Session::get('alert', []) as $key => $messages)
<div class="alert alert-{{ $key }} alert-dismissible fade show alert-blocks fade-in" role="alert">
  @foreach ((array) $messages as $message)
    <div>{!! $message !!}</div>
  @endforeach
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
@endforeach

<div id="errors-list-template" class="d-none">
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0"></ul>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="unmark_errors()"><span aria-hidden="true">&times;</span></button>
  </div>
</div>

<div id="vue-errors">
  <transition name="fade" mode="out-in">
    <div class="alert alert-danger alert-dismissible fade show d-none" :class="{'d-block': 1}" role="alert" v-if="Object.keys(errors).length">
      <ul class="mb-0">
        <template v-for="error in errors">
        <li v-for="message in error" v-text="message"></li>
        </template>
      </ul>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close" @click.prevent="cleanErrors()"><span aria-hidden="true">&times;</span></button>
    </div>
  </transition>
</div>
--}}
