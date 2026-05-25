@extends('admin.layouts.master')
@section('title', 'المؤلفون')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'المؤلفون',
            'arr' => [['title' => 'المؤلفون', 'link' => route('admin.authors.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة المؤلفين</h4>
                        <a href="{{ route('admin.authors.create') }}" class="btn btn-primary">إضافة مؤلف</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalAuthors">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchAuthors" class="form-control" placeholder="بحث بالاسم أو الجنسية...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم الأول</th>
                                    <th>الاسم الأخير</th>
                                    <th>الاسم الكامل</th>
                                    <th>الجنسية</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="authorsTableBody"></tbody>
                        </table>
                        <div id="authorsPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/authors.js') }}"></script>
@endpush
