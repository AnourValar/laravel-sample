@props(['value', 'display' => 'id', 'default' => ''])

<x-form::link
  :value="$value"
  :display="$display"
  :default="$default"
  entity="user"
  {{ $attributes }}
/>
