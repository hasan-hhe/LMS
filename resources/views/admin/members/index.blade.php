@extends('admin.layouts.master')
@section('title', 'الأعضاء')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الأعضاء',
            'arr' => [['title' => 'الأعضاء', 'link' => route('admin.members.index')]],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title mb-0">قائمة الأعضاء</h4>
                        <a href="{{ route('admin.members.create') }}" class="btn btn-primary">إضافة عضو</a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 id="totalMembers">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchMembers" class="form-control" placeholder="بحث بالاسم أو البريد أو الهاتف...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover table-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الهاتف</th>
                                    <th>رقم الهوية</th>
                                    <th>انتهاء العضوية</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="membersTableBody"></tbody>
                        </table>
                        <div id="membersPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/members.js') }}"></script>
@endpush
