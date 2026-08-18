@extends('admin.layouts.master')
@section('title', 'تقرير اقتصاد النقاط')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'اقتصاد النقاط',
            'arr' => [
                ['title' => 'التقارير', 'link' => ''],
                ['title' => 'اقتصاد النقاط', 'link' => route('admin.reports.points')],
            ],
        ])

        <div id="pointsSummaryReport">
            <div class="row">
                @foreach ([
                    ['pointsTotalBalance', 'إجمالي أرصدة المستخدمين', 'primary'],
                    ['pointsTotalTopUps', 'إجمالي الشحن', 'success'],
                    ['pointsTotalSpent', 'إجمالي الإنفاق', 'danger'],
                    ['pointsTotalRewards', 'إجمالي المكافآت', 'info'],
                    ['pointsCodesUnused', 'أكواد غير مستخدمة', 'warning'],
                    ['pointsCodesUsed', 'أكواد مستخدمة', 'secondary'],
                ] as [$id, $label, $color])
                    <div class="col-sm-6 col-lg-4 mb-3">
                        <div class="card bg-{{ $color }} text-white">
                            <div class="card-body">
                                <p class="mb-1">{{ $label }}</p>
                                <h4 id="{{ $id }}">-</h4>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">تصدير التقارير</h4></div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label for="pointsExportFrom" class="form-label">من تاريخ</label>
                            <input type="date" id="pointsExportFrom" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="pointsExportTo" class="form-label">إلى تاريخ</label>
                            <input type="date" id="pointsExportTo" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="pointsExportType" class="form-label">نوع الحركة</label>
                            <select id="pointsExportType" class="form-select">
                                <option value="">الكل</option>
                                <option value="top_up">شحن</option>
                                <option value="spend">إنفاق</option>
                                <option value="reward">مكافأة</option>
                                <option value="adjust">تعديل</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button id="pointsExportButton" class="btn btn-primary w-100">
                                <i class="fas fa-download"></i> تصدير حركات النقاط
                            </button>
                        </div>
                    </div>
                    <button id="finesExportButton" class="btn btn-outline-danger me-2">
                        <i class="fas fa-file-csv"></i> تصدير الغرامات
                    </button>
                    <button id="overdueExportButton" class="btn btn-outline-warning">
                        <i class="fas fa-file-csv"></i> تصدير المتأخرات
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/reports-points.js') }}"></script>
@endpush
