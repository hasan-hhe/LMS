<!DOCTYPE html>
<html lang="ar" style="overflow-x: hidden;">

@include('admin.layouts.head')

<body dir="rtl" data-require-auth="true">
  <div class="wrapper">
    @include('admin.layouts.sidebar')
    <div class="main-panel">
      @include('admin.layouts.header')
      @yield('main-content')
      @include('admin.layouts.footer')
    </div>
  </div>
  @include('admin.layouts.scripts')
</body>

</html>
