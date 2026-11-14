<x-pulse>
  <livewire:pulse.servers cols="full" ignore-after="30 days" />

  <livewire:pulse.usage cols="4" rows="2" />
  <livewire:pulse.cache cols="8" />
  <livewire:pulse.slow-queries cols="8" />

  <livewire:pulse.queues cols="6" />
  <livewire:anourvalar.pulse.latency-jobs cols="6" />

  <livewire:pulse.exceptions cols="6" />
  <livewire:pulse.slow-requests cols="6" />

  <livewire:pulse.slow-jobs cols="6" />
  <livewire:anourvalar.pulse.schedule cols="6" />

  <livewire:anourvalar.pulse.http-requests-count cols="6" />
  <livewire:anourvalar.pulse.http-requests-avg cols="6" />

  <livewire:anourvalar.pulse.database cols="6" />
  <livewire:anourvalar.pulse.ping cols="6" />

  <livewire:anourvalar.pulse.fpm cols="6" />
  <livewire:anourvalar.pulse.anourvalar-httpclient cols="6" />
  {{-- <livewire:pulse.slow-outgoing-requests cols="6" /> --}}

  <livewire:anourvalar.pulse.slow-commands cols="6" />
</x-pulse>
