@extends('admin.layouts.master')
@section('title', 'جرد المخزون')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'جرد المخزون',
            'arr' => [
                ['title' => 'التقارير', 'link' => ''],
                ['title' => 'جرد المخزون', 'link' => route('admin.reports.inventory')],
            ],
        ])
        <div class="col-md-12" id="inventoryReport">
            <div class="row">
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <p class="mb-1">إجمالي الكتب (عناوين)</p>
                            <h4 id="invTotalBooks">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <p class="mb-1">إجمالي النسخ</p>
                            <h4 id="invTotalInstances">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <p class="mb-1">نسخ متاحة</p>
                            <h4 id="invAvailableInstances">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-warning text-white">
                        <div class="card-body">
                            <p class="mb-1">نسخ مستعارة</p>
                            <h4 id="invBorrowedInstances">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">نسخ محجوزة</p>
                            <h4 id="invReservedInstances">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">نسخ تالفة</p>
                            <h4 id="invDamagedInstances">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">نسخ مفقودة</p>
                            <h4 id="invLostInstances">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">إجمالي الأعضاء</p>
                            <h4 id="invTotalMembers">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="card stat-card bg-danger text-white">
                        <div class="card-body">
                            <p class="mb-1">عضويات منتهية</p>
                            <h4 id="invExpiredMemberships">-</h4>
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
