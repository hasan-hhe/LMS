@extends('admin.layouts.master')
@section('title', 'الاستعارات')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الاستعارات',
            'arr' => [['title' => 'الاستعارات', 'link' => route('admin.borrowings.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">الاستعارات النشطة</h4>
                        <a href="{{ route('admin.borrowings.create') }}" class="btn btn-primary">تسجيل استعارة</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalBorrowings">العدد: 0</h5>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العضو</th>
                                    <th>الكتاب</th>
                                    <th>تاريخ البداية</th>
                                    <th>تاريخ النهاية</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="borrowingsTableBody"></tbody>
                        </table>
                        <div id="borrowingsPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/borrowings.js') }}"></script>
@endpush
