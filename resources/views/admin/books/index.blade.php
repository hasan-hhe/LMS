@extends('admin.layouts.master')
@section('title', 'الكتب')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الكتب',
            'arr' => [['title' => 'الكتب', 'link' => route('admin.books.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة الكتب</h4>
                        <a href="{{ route('admin.books.create') }}" class="btn btn-primary">إضافة كتاب</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalBooks">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchBooks" class="form-control" placeholder="بحث بالعنوان أو ISBN...">
                        </div>
                        <div class="col-md-3">
                            <select id="filterCategory" class="form-control"><option value="">كل التصنيفات</option></select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterAuthor" class="form-control"><option value="">كل المؤلفين</option></select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ISBN</th>
                                    <th>العنوان</th>
                                    <th>المؤلف</th>
                                    <th>التصنيف</th>
                                    <th>السنة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="booksTableBody"></tbody>
                        </table>
                        <div id="booksPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/books.js') }}"></script>
@endpush
