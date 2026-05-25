@extends('admin.layouts.master')
@section('title', 'تعديل نسخة كتاب')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'تعديل نسخة كتاب',
            'arr' => [
                ['title' => 'نسخ الكتب', 'link' => route('admin.book-instances.index')],
                ['title' => 'تعديل', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="bookInstanceForm">
                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label>الكتاب (ISBN)</label>
                                <input type="text" id="bookIsbnDisplay" class="form-control" disabled>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>حالة النسخة *</label>
                                <select name="state_id" id="state_id" class="form-control" required>
                                    <option value="">اختر الحالة</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>الوضع *</label>
                                <select name="condition" id="condition" class="form-control" required>
                                    <option value="">اختر الوضع</option>
                                    <option value="new">جديد</option>
                                    <option value="almost_new">شبه جديد</option>
                                    <option value="worn">مستعمل</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">تحديث</button>
                            <a href="{{ route('admin.book-instances.index') }}" class="btn btn-secondary">إلغاء</a>
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
<script src="{{ asset('js/dashboard/modules/book-instances.js') }}"></script>
@endpush
