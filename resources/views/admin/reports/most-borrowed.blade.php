@extends('admin.layouts.master')
@section('title', 'الكتب الأكثر استعارة')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الكتب الأكثر استعارة',
            'arr' => [
                ['title' => 'التقارير', 'link' => ''],
                ['title' => 'الأكثر استعارة', 'link' => route('admin.reports.most-borrowed')],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">تقرير الكتب الأكثر استعارة</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ISBN</th>
                                    <th>العنوان</th>
                                    <th>عدد الاستعارات</th>
                                </tr>
                            </thead>
                            <tbody id="mostBorrowedReportBody">
                                <tr><td colspan="4" class="page-loading"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/reports.js') }}"></script>
@endpush
