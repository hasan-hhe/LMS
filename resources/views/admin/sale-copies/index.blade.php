@extends('admin.layouts.master')
@section('title', 'نسخ الكتب البيع')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'نسخ الكتب البيع',
            'arr' => [['title' => 'نسخ الكتب البيع', 'link' => route('admin.sale-copies.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة نسخ البيع</h4>
                        <a href="{{ route('admin.sale-copies.create') }}" class="btn btn-primary">إضافة نسخ بيع</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalSaleCopies">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchSaleCopies" class="form-control" placeholder="بحث بعنوان الكتاب أو ISBN...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ISBN</th>
                                    <th>عنوان الكتاب</th>
                                    <th>المؤلف</th>
                                    <th>عدد نسخ البيع</th>
                                    <th>سعر النقاط</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="saleCopiesTableBody"></tbody>
                        </table>
                        <div id="saleCopiesPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/sale-copies.js') }}"></script>
@endpush
