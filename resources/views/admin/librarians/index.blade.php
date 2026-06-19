@extends('admin.layouts.master')
@section('title', 'أمناء المكتبة')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'أمناء المكتبة',
            'arr' => [['title' => 'أمناء المكتبة', 'link' => route('admin.librarians.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة أمناء المكتبة</h4>
                        <a href="{{ route('admin.librarians.create') }}" class="btn btn-primary">إضافة أمين مكتبة</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalLibrarians">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchLibrarians" class="form-control" placeholder="بحث بالاسم أو البريد أو الهاتف...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الهاتف</th>
                                    <th>رقم الهوية</th>
                                    <th>حالة الحساب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="librariansTableBody"></tbody>
                        </table>
                        <div id="librariansPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/librarians.js') }}"></script>
@endpush
