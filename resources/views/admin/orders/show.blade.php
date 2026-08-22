@extends('admin.layouts.master')
@section('title', 'تفاصيل الطلب')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'تفاصيل الطلب',
            'arr' => [
                ['title' => 'الطلبات', 'link' => route('admin.orders.index')],
                ['title' => 'تفاصيل', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" id="orderShowContent">
                    <div class="page-loading"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</div>
                </div>
                <div class="card-footer">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="orderStateSelect" class="form-label mb-1">تغيير الحالة</label>
                            <select id="orderStateSelect" class="form-control">
                                <option value="">اختر الحالة</option>
                            </select>
                        </div>
                        <div class="col-md-5 d-none" id="orderStateReasonWrap">
                            <label for="orderStateReason" class="form-label mb-1">سبب الرفض أو الإلغاء *</label>
                            <textarea id="orderStateReason" class="form-control" rows="2" maxlength="500" placeholder="اكتب السبب الذي سيظهر للعضو"></textarea>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="button" id="btnUpdateOrderState" class="btn btn-primary">تحديث</button>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">رجوع</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_ORDER_ID = @json($id);</script>
<script src="{{ asset('js/dashboard/modules/orders.js') }}"></script>
@endpush
