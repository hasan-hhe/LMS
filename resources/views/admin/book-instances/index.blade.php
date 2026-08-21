@extends('admin.layouts.master')
@section('title', 'نسخ الكتب الاستعارة')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'نسخ الكتب الاستعارة',
            'arr' => [['title' => 'نسخ الكتب الاستعارة', 'link' => route('admin.book-instances.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة نسخ الاستعارة</h4>
                        <a href="{{ route('admin.book-instances.create') }}" class="btn btn-primary">إضافة نسخة استعارة</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalBookInstances">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchBookInstances" class="form-control" placeholder="بحث بعنوان الكتاب أو ISBN...">
                        </div>
                        <div class="col-md-4">
                            <select id="filterStateId" class="form-control">
                                <option value="">كل الحالات</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>عنوان الكتاب</th>
                                    <th>عدد النسخ</th>
                                    <th>المتاح</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="bookInstancesTableBody"></tbody>
                        </table>
                        <div id="bookInstancesPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bookCopiesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="copiesModalTitle">نسخ الكتاب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم النسخة</th>
                                <th>الحالة</th>
                                <th>الوضع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="copiesModalBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/book-instances.js') }}"></script>
@endpush
