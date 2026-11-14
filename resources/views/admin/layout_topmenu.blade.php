@inject('layoutHelper', 'App\Helpers\LayoutHelper')

@foreach ($layoutHelper->getMenu($menuRoute ?? null, Auth::user(), 'admin.menu') as $key => $item)
  @if (isset($item['dropdown']))
    <li class="nav-item dropdown {{ $item['additional']['opened'] ?? $item['is_active'] ? 'menu-open' : '' }}">
      <a
        href="#"
        id="dropdownSubMenu{{ $key }}"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
        class="nav-link dropdown-toggle"
        class="{{ $item['is_active'] ? 'active' : '' }}"
      >
        @if (isset($item['additional']['icon']))
          <i class="nav-icon fas {{ $item['additional']['icon'] }}"></i>
        @endif
        {{ $item['title'] }}
      </a>
      <ul aria-labelledby="dropdownSubMenu{{ $key }}" class="dropdown-menu border-0 shadow">
        @foreach ($item['dropdown'] as $child)
          @if (isset($child['route']))
            <li>
              <a href="{{ $child['url'] }}" class="dropdown-item {{ $child['is_active'] ? 'active' : '' }}" target="{{ $child['additional']['target'] ?? '_self' }}">
                <i class="fas nav-icon {{ $child['additional']['icon'] ?? 'fa-circle' }}"></i>
                {{ $child['title'] }}
                @foreach ($child['counter'] as $class => $counter)
                  @if ($counter)
                    <span class="badge badge-{{ $class }} right">{{ $counter }}</span>
                    @break
                  @endif
                @endforeach
              </a>
            </li>
          @elseif (!$loop->first && !$loop->last)
            <div class="dropdown-divider"></div>
          @endif
        @endforeach
      </ul>
    </li>
  @else
    <li class="nav-item">
      <a class="nav-link {{ $item['is_active'] ? 'active' : '' }}" href="{{ $item['url'] }}" target="{{ $item['additional']['target'] ?? '_self' }}">
        @if (isset($item['additional']['icon']))
          <i class="nav-icon fas {{ $item['additional']['icon'] }}"></i>
        @endif
        {{ $item['title'] }}
        @foreach ($item['counter'] as $class => $counter)
          @if ($counter)
            <span class="badge badge-{{ $class }} right">{{ $counter }}</span>
            @break
          @endif
        @endforeach
      </a>
    </li>
  @endif
@endforeach
