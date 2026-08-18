@extends('admin.layouts.master')
@section('title', 'التقييمات')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إدارة التقييمات',
            'arr' => [['title' => 'التقييمات', 'link' => route('admin.reviews.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">قائمة التقييمات</h4>
                </div>
                <div class="card-body">
                    <h5 id="totalReviews">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchReviews" class="form-control" placeholder="بحث (تعليق / ISBN / عضو)">
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="filterIsbn" class="form-control" placeholder="ISBN">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الكتاب</th>
                                    <th>العضو</th>
                                    <th>التقييم</th>
                                    <th>التعليق</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="reviewsTableBody"></tbody>
                        </table>
                        <div id="reviewsPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/reviews.js') }}"></script>
@endpush
