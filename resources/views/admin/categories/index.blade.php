@extends('admin.layouts.master')
@section('title', 'التصنيفات')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'التصنيفات',
            'arr' => [['title' => 'التصنيفات', 'link' => route('admin.categories.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة التصنيفات</h4>
                        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">إضافة تصنيف</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalCategories">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchCategories" class="form-control" placeholder="بحث بالعنوان أو الوصف...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العنوان</th>
                                    <th>الوصف</th>
                                    <th>عدد الكتب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody"></tbody>
                        </table>
                        <div id="categoriesPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/categories.js') }}"></script>
@endpush
