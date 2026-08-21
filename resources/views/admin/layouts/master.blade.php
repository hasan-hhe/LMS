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
  <div class="modal fade" id="lmsFilePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="lmsFilePreviewTitle">عرض الملف</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
        </div>
        <div class="modal-body text-center" id="lmsFilePreviewBody"></div>
      </div>
    </div>
  </div>
  @include('admin.layouts.scripts')
</body>

</html>
