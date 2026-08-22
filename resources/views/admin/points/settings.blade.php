@extends('admin.layouts.master')
@section('title', 'إعدادات عامة')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إعدادات عامة',
            'arr' => [['title' => 'إعدادات عامة', 'link' => route('admin.points.settings')]],
        ])
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">سياسة النقاط والغرامات</h4></div>
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
                            <hr>
                            <h5 class="mb-3">إعدادات الغرامة اليومية</h5>
                            <div class="form-group mb-3">
                                <label>الغرامة لليوم (ل.س) *</label>
                                <input type="number" name="fine_per_day_syp" min="0.01" step="0.01" class="form-control" required>
                                <small class="form-text text-muted">المبلغ بالليرة السورية عن كل يوم تأخير.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>الغرامة لليوم (نقاط) *</label>
                                <input type="number" name="fine_per_day_points" min="0" class="form-control" required>
                                <small class="form-text text-muted">تُخصم يومياً من رصيد العضو عند التأخير. إن لم يكفِ الرصيد تتجمّع كمبلغ واحد بالليرة أو النقاط.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <hr>
                            <h5 class="mb-3">تمديد الاستعارة</h5>
                            <div class="form-group mb-3">
                                <label>نقاط تمديد الاستعارة لليوم الواحد *</label>
                                <input type="number" name="extension_per_day_points" min="0" class="form-control" required>
                                <small class="form-text text-muted">تُخصم من رصيد العضو عن كل يوم يُضاف بعد تاريخ انتهاء الاستعارة الحالي. القيمة 0 تعني تمديداً مجانياً.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>مدة الاستعارة الافتراضية (أيام) *</label>
                                <input type="number" name="loan_period_days" min="1" class="form-control" required>
                                <small class="form-text text-muted">احتياطي فقط إذا لم تُحدد مدة استعارة للكتاب نفسه.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <hr>
                            <h5 class="mb-3">عضوية الإعارة</h5>
                            <div class="form-group mb-3">
                                <label>نقاط الاشتراك أو التمديد *</label>
                                <input type="number" name="membership_points" min="0" class="form-control" required>
                                <small class="form-text text-muted">يخصمها العضو من رصيده عند الاشتراك أو تمديد العضوية من التطبيق.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>مدة العضوية (أيام) *</label>
                                <input type="number" name="membership_days" min="1" class="form-control" required>
                                <small class="form-text text-muted">تُضاف إلى تاريخ انتهاء العضوية الحالي إن كانت سارية، وإلا تبدأ من اليوم.</small>
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
