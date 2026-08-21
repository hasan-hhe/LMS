@extends('admin.layouts.master')
@section('title', 'المحتوى الرقمي')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'المحتوى الرقمي',
            'arr' => [['title' => 'المحتوى الرقمي', 'link' => route('admin.digital-assets.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة المحتوى الرقمي</h4>
                        <a href="{{ route('admin.digital-assets.create') }}" class="btn btn-primary">إضافة محتوى رقمي</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalDigitalAssets">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchDigitalAssets" class="form-control" placeholder="بحث بعنوان الكتاب أو ISBN...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الكتاب</th>
                                    <th>ISBN</th>
                                    <th>PDF</th>
                                    <th>صوتي</th>
                                    <th>الإتاحة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="digitalAssetsTableBody"></tbody>
                        </table>
                        <div id="digitalAssetsPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/digital-assets.js') }}"></script>
@endpush
