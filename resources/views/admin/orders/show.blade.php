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
                <div class="card-footer d-flex gap-2 flex-wrap align-items-center">
                    <div class="form-group mb-0">
                        <label class="me-2">تغيير الحالة:</label>
                        <select id="orderStateSelect" class="form-control d-inline-block" style="width: auto; min-width: 180px;"></select>
                        <button type="button" id="btnUpdateOrderState" class="btn btn-primary ms-2">تحديث</button>
                    </div>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">رجوع</a>
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
