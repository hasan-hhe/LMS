@extends('admin.layouts.master')
@section('title', 'لوحة التحكم')
@section('main-content')
    <div class="container">
        <div class="page-inner">
            @include('admin.components.page-header', [
                'title' => 'لوحة التحكم',
                'arr' => [['title' => 'الرئيسية', 'link' => '']],
            ])

            <div class="row" id="statsCards">
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <p class="mb-1">إجمالي الكتب</p>
                            <h4 id="statTotalBooks">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <p class="mb-1">الأعضاء</p>
                            <h4 id="statTotalMembers">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <p class="mb-1">استعارات نشطة</p>
                            <h4 id="statActiveBorrowings">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card bg-danger text-white">
                        <div class="card-body">
                            <p class="mb-1">استعارات متأخرة</p>
                            <h4 id="statOverdueBorrowings">-</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">غرامات غير مدفوعة</p>
                            <h4 id="statFinesUnpaid">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">غرامات محصّلة</p>
                            <h4 id="statFinesCollected">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">أعضاء جدد (الشهر)</p>
                            <h4 id="statNewMembers">-</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-1">استعارات (الشهر)</p>
                            <h4 id="statBorrowingsMonth">-</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">الاستعارات</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="borrowingsChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">الكتب الأكثر استعارة</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>العنوان</th>
                                            <th>عدد الاستعارات</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mostBorrowedBody">
                                        <tr><td colspan="3" class="page-loading">جاري التحميل...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body d-flex gap-2 flex-wrap">
                            <a href="{{ route('admin.borrowings.create') }}" class="btn btn-primary">استعارة جديدة</a>
                            <a href="{{ route('admin.books.create') }}" class="btn btn-secondary">إضافة كتاب</a>
                            <a href="{{ route('admin.members.create') }}" class="btn btn-secondary">إضافة عضو</a>
                            <a href="{{ route('admin.reports.overdue') }}" class="btn btn-danger">الاستعارات المتأخرة</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard/modules/dashboard.js') }}"></script>
@endpush
