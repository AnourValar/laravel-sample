@if ($field->fileVirtuals->count())
  <div class="callout callout-info mb-0">
    @foreach ($field->fileVirtuals as $fileVirtual)
      <a
        href="{{ $fileVirtual->url }}"
        class="text-info pr-3"
        target="_blank"
        rel="noopener noreferrer"
      >
        <i class="fa-solid fa-link"></i> {{ $fileVirtual->name_title }}
      </a>
    @endforeach
  </div>
@endif
