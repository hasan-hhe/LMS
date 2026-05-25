@extends('admin.layouts.master')
@section('title', 'طلب جديد')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'طلب جديد',
            'arr' => [
                ['title' => 'الطلبات', 'link' => route('admin.orders.index')],
                ['title' => 'إضافة', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="orderForm">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>المستخدم *</label>
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">اختر المستخدم...</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3">عناصر الطلب</h5>
                        <div id="orderItemsContainer"></div>
                        <button type="button" id="btnAddOrderItem" class="btn btn-secondary mb-3">
                            <i class="fa fa-plus"></i> إضافة عنصر
                        </button>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ الطلب</button>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/orders.js') }}"></script>
@endpush
