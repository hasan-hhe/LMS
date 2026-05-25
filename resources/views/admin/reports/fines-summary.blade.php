@extends('admin.layouts.master')
@section('title', 'ملخص الغرامات')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'ملخص الغرامات',
            'arr' => [
                ['title' => 'التقارير', 'link' => ''],
                ['title' => 'ملخص الغرامات', 'link' => route('admin.reports.fines-summary')],
            ],
        ])
        <div class="col-md-12" id="finesSummaryReport">
            <div class="row">
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <p class="mb-1">إجمالي الغرامات</p>
                            <h4 id="finesTotalCount">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <p class="mb-1">إجمالي المبلغ</p>
                            <h4 id="finesTotalAmount">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <p class="mb-1">المبلغ المدفوع</p>
                            <h4 id="finesPaidAmount">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-danger text-white">
                        <div class="card-body">
                            <p class="mb-1">المبلغ غير المدفوع</p>
                            <h4 id="finesUnpaidAmount">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">غرامات مدفوعة</p>
                            <h4 id="finesPaidCount">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">غرامات غير مدفوعة</p>
                            <h4 id="finesUnpaidCount">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">متوسط أيام التأخير</p>
                            <h4 id="finesAvgDaysLate">-</h4>
                        </div>
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
