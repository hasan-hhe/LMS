@extends('admin.layouts.master')
@section('title', 'إضافة مؤلف')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إضافة مؤلف',
            'arr' => [
                ['title' => 'المؤلفون', 'link' => route('admin.authors.index')],
                ['title' => 'إضافة', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="authorForm">
                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label>الاسم الأول *</label>
                                <input type="text" name="firstname" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>الاسم الأخير *</label>
                                <input type="text" name="lastname" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>الجنسية *</label>
                                <input type="text" name="nationality" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.authors.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_ENTITY_ID = null;</script>
<script src="{{ asset('js/dashboard/modules/authors.js') }}"></script>
@endpush
