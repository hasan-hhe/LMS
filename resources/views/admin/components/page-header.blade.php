@php
  $textAlign = 'left';
@endphp

<div class="page-header">
  <h3 class="fw-bold mb-3">{{ $title }}</h3>
  <ul class="breadcrumbs mb-3">
    <li class="nav-home">
      <a href="{{ route('admin.dashboard') }}">
        <i class="icon-home"></i>
      </a>
    </li>
    <li class="separator">
      <i class="icon-arrow-{{ $textAlign }}"></i>
    </li>
    <li class="nav-item">
      <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
    </li>

    @foreach ($arr as $ar)
      <li class="separator">
        <i class="icon-arrow-{{ $textAlign }}"></i>
      </li>
      <li class="nav-item">
        @if (!empty($ar['link']))
          <a href="{{ $ar['link'] }}">{{ $ar['title'] }}</a>
        @else
          <span>{{ $ar['title'] }}</span>
        @endif
      </li>
    @endforeach
  </ul>
</div>
