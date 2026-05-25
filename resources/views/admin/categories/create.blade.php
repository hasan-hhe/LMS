@extends('admin.layouts.master')
@section('title', 'إضافة تصنيف')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إضافة تصنيف',
            'arr' => [
                ['title' => 'التصنيفات', 'link' => route('admin.categories.index')],
                ['title' => 'إضافة', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="categoryForm">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>العنوان *</label>
                                <input type="text" name="title" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-12 form-group mb-3">
                                <label>الوصف *</label>
                                <textarea name="discription" class="form-control" rows="3" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">إلغاء</a>
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
<script src="{{ asset('js/dashboard/modules/categories.js') }}"></script>
@endpush
