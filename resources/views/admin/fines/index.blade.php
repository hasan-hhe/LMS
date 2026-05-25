@extends('admin.layouts.master')
@section('title', 'الغرامات')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الغرامات',
            'arr' => [['title' => 'الغرامات', 'link' => route('admin.fines.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">قائمة الغرامات</h4>
                </div>
                <div class="card-body">
                    <h5 id="totalFines">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-3">
                            <select id="filterFinePaid" class="form-control">
                                <option value="">كل الحالات</option>
                                <option value="false">غير مدفوعة</option>
                                <option value="true">مدفوعة</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العضو</th>
                                    <th>أيام التأخير</th>
                                    <th>الغرامة</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الدفع</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="finesTableBody"></tbody>
                        </table>
                        <div id="finesPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/fines.js') }}"></script>
@endpush
