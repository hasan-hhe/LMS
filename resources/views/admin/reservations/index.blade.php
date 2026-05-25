@extends('admin.layouts.master')
@section('title', 'الحجوزات')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الحجوزات',
            'arr' => [['title' => 'الحجوزات', 'link' => route('admin.reservations.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة الحجوزات</h4>
                        <a href="{{ route('admin.reservations.create') }}" class="btn btn-primary">إضافة حجز</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalReservations">العدد: 0</h5>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الكتاب</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الحجز</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="reservationsTableBody"></tbody>
                        </table>
                        <div id="reservationsPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/reservations.js') }}"></script>
@endpush
