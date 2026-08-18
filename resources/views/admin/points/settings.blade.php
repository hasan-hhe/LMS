@extends('admin.layouts.master')
@section('title', 'إعدادات النقاط')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إعدادات النقاط',
            'arr' => [['title' => 'إعدادات النقاط', 'link' => route('admin.points.settings')]],
        ])
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">سياسة احتساب النقاط</h4></div>
                    <div class="card-body">
                        <form id="pointsSettingsForm">
                            <div class="form-group mb-3">
                                <label>الليرات السورية لكل نقطة *</label>
                                <input type="number" name="syp_per_point" min="0" step="0.01" class="form-control" required>
                                <small class="form-text text-muted">قيمة التحويل بين السعر بالليرة السورية والنقاط.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>مكافأة الإرجاع في الوقت *</label>
                                <input type="number" name="reward_return_on_time" min="0" class="form-control" required>
                                <small class="form-text text-muted">عدد النقاط المضافة عند إعادة الكتاب ضمن الموعد.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_POINTS_PAGE = 'settings';</script>
<script src="{{ asset('js/dashboard/modules/points.js') }}"></script>
@endpush
