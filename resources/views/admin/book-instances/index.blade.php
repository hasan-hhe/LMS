@extends('admin.layouts.master')
@section('title', 'نسخ الكتب')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'نسخ الكتب',
            'arr' => [['title' => 'نسخ الكتب', 'link' => route('admin.book-instances.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة نسخ الكتب</h4>
                        <a href="{{ route('admin.book-instances.create') }}" class="btn btn-primary">إضافة نسخة</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalBookInstances">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <select id="filterBookIsbn" class="form-control">
                                <option value="">كل الكتب</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filterStateId" class="form-control">
                                <option value="">كل الحالات</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ISBN</th>
                                    <th>عنوان الكتاب</th>
                                    <th>الحالة</th>
                                    <th>الوضع</th>
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
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/book-instances.js') }}"></script>
@endpush
