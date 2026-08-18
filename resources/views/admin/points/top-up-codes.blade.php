@extends('admin.layouts.master')
@section('title', 'أكواد شحن النقاط')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'أكواد شحن النقاط',
            'arr' => [['title' => 'أكواد الشحن', 'link' => route('admin.points.top-up-codes')]],
        ])
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">توليد أكواد جديدة</h4></div>
                    <div class="card-body">
                        <form id="generateTopUpCodesForm">
                            <div class="form-group mb-3">
                                <label>عدد الأكواد *</label>
                                <input type="number" name="count" min="1" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>قيمة الكود بالنقاط *</label>
                                <input type="number" name="points_value" min="1" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>تاريخ الانتهاء</label>
                                <input type="datetime-local" name="expires_at" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>تخصيص لعضو (اختياري)</label>
                                <select name="user_id" id="topUpCodeUserId" class="form-control">
                                    <option value="">متاح لأي عضو</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">توليد الأكواد</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">الأكواد المولدة</h4></div>
                    <div class="card-body">
                        <h5 id="totalTopUpCodes">العدد: 0</h5>
                        <div class="table-responsive">
                            <table class="table display table-striped table-hover table-datatable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الكود</th>
                                        <th>النقاط</th>
                                        <th>العضو</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الانتهاء</th>
                                    </tr>
                                </thead>
                                <tbody id="topUpCodesTableBody"></tbody>
                            </table>
                            <div id="topUpCodesPagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_POINTS_PAGE = 'top-up-codes';</script>
<script src="{{ asset('js/dashboard/modules/points.js') }}"></script>
@endpush
