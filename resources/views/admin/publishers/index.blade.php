@extends('admin.layouts.master')
@section('title', 'دور النشر')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'دور النشر',
            'arr' => [['title' => 'دور النشر', 'link' => route('admin.publishers.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة دور النشر</h4>
                        <a href="{{ route('admin.publishers.create') }}" class="btn btn-primary">إضافة دار نشر</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalPublishers">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchPublishers" class="form-control" placeholder="بحث بالاسم أو الموقع...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th>الموقع</th>
                                    <th>عدد الكتب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="publishersTableBody"></tbody>
                        </table>
                        <div id="publishersPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/publishers.js') }}"></script>
@endpush
