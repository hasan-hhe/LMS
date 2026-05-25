@extends('admin.layouts.master')
@section('title', 'تعديل دار نشر')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'تعديل دار نشر',
            'arr' => [
                ['title' => 'دور النشر', 'link' => route('admin.publishers.index')],
                ['title' => 'تعديل', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="publisherForm">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>الاسم *</label>
                                <input type="text" name="name" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>الموقع *</label>
                                <input type="text" name="location" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">تحديث</button>
                            <a href="{{ route('admin.publishers.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_ENTITY_ID = @json($id);</script>
<script src="{{ asset('js/dashboard/modules/publishers.js') }}"></script>
@endpush
