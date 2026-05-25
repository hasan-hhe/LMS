@extends('admin.layouts.master')
@section('title', 'تسجيل استعارة')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'تسجيل استعارة',
            'arr' => [
                ['title' => 'الاستعارات', 'link' => route('admin.borrowings.index')],
                ['title' => 'تسجيل', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="borrowingForm">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>العضو *</label>
                                <select name="member_id" id="member_id" class="form-control" required>
                                    <option value="">اختر العضو</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>نسخة الكتاب *</label>
                                <select name="book_instance_id" id="book_instance_id" class="form-control" required>
                                    <option value="">اختر نسخة الكتاب</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>تاريخ انتهاء الاستعارة *</label>
                                <input type="date" name="end_date" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>تكلفة الاستعارة</label>
                                <input type="number" step="0.01" min="0" name="borrowing_cost" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.borrowings.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/borrowings.js') }}"></script>
@endpush
