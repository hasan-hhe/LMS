@extends('admin.layouts.master')
@section('title', 'تفاصيل العضو')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'تفاصيل العضو',
            'arr' => [
                ['title' => 'الأعضاء', 'link' => route('admin.members.index')],
                ['title' => 'تفاصيل', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="card bg-primary-gradient text-white mb-4">
                        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <div class="text-white-50">رصيد النقاط</div>
                                <h2 class="mb-0"><span id="memberPointsBalance">--</span> نقطة</h2>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" id="btnMemberTopUp" class="btn btn-light">
                                    <i class="fas fa-ticket-alt"></i> شحن بكود
                                </button>
                                <button type="button" id="btnAdjustMemberPoints" class="btn btn-warning" data-role="ADMIN">
                                    <i class="fas fa-sliders-h"></i> تعديل الرصيد
                                </button>
                            </div>
                        </div>
                    </div>
                    <ul class="nav nav-tabs nav-line nav-color-secondary" id="memberTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profileTab" role="tab">البيانات الشخصية</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="borrowings-tab" data-bs-toggle="tab" href="#borrowingsTab" role="tab">الاستعارات</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="fines-tab" data-bs-toggle="tab" href="#finesTab" role="tab">الغرامات</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="points-tab" data-bs-toggle="tab" href="#pointsTab" role="tab">سجل النقاط</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="memberTabContent">
                        <div class="tab-pane fade show active" id="profileTab" role="tabpanel">
                            <div id="memberProfileContent">
                                <div class="page-loading"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="borrowingsTab" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table display table-striped table-hover table-datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الكتاب</th>
                                            <th>تاريخ البداية</th>
                                            <th>تاريخ النهاية</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody id="memberBorrowingsBody"></tbody>
                                </table>
                                <div id="memberBorrowingsPagination"></div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="finesTab" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table display table-striped table-hover table-datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>أيام التأخير</th>
                                            <th>الغرامة</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الدفع</th>
                                        </tr>
                                    </thead>
                                    <tbody id="memberFinesBody"></tbody>
                                </table>
                                <div id="memberFinesPagination"></div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pointsTab" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table display table-striped table-hover table-datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>التغيير</th>
                                            <th>نوع العملية</th>
                                            <th>البيان</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="memberPointsHistoryBody"></tbody>
                                </table>
                                <div id="memberPointsHistoryPagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-primary">تعديل</a>
                    <a href="{{ route('admin.members.index') }}" class="btn btn-secondary">رجوع</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_MEMBER_ID = @json($member); window.LMS_MEMBER_SHOW = true;</script>
<script src="{{ asset('js/dashboard/modules/members.js') }}"></script>
@endpush
