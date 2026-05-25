@extends('admin.layouts.master')
@section('title', 'الطلبات')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الطلبات',
            'arr' => [['title' => 'الطلبات', 'link' => route('admin.orders.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة الطلبات</h4>
                        <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">طلب جديد</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalOrders">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <select id="filterOrderState" class="form-control">
                                <option value="">كل الحالات</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>رقم الطلب</th>
                                    <th>المستخدم</th>
                                    <th>الحالة</th>
                                    <th>إجمالي الكمية</th>
                                    <th>إجمالي السعر</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody"></tbody>
                        </table>
                        <div id="ordersPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/orders.js') }}"></script>
@endpush
