@extends('admin.layouts.master')
@section('title', 'تقرير الاستعارات المتأخرة')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الاستعارات المتأخرة',
            'arr' => [
                ['title' => 'التقارير', 'link' => ''],
                ['title' => 'المتأخرة', 'link' => route('admin.reports.overdue')],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">تقرير الاستعارات المتأخرة</h4>
                </div>
                <div class="card-body">
                    <h5 id="overdueTotal" class="mb-3">إجمالي المتأخرة: -</h5>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العضو</th>
                                    <th>الكتاب</th>
                                    <th>تاريخ الاستحقاق</th>
                                    <th>أيام التأخير</th>
                                </tr>
                            </thead>
                            <tbody id="overdueReportBody">
                                <tr><td colspan="5" class="page-loading"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</td></tr>
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
