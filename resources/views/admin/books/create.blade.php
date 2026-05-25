@extends('admin.layouts.master')
@section('title', 'إضافة كتاب')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إضافة كتاب',
            'arr' => [
                ['title' => 'الكتب', 'link' => route('admin.books.index')],
                ['title' => 'إضافة', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="bookForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>ISBN *</label>
                                <input type="text" name="ISBN" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>العنوان *</label>
                                <input type="text" name="title" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>المؤلف *</label>
                                <select name="auther_id" id="auther_id" class="form-control" required></select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>التصنيف *</label>
                                <select name="catagory_id" id="catagory_id" class="form-control" required></select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>دار النشر *</label>
                                <select name="publisher_id" id="publisher_id" class="form-control" required></select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-12 form-group mb-3">
                                <label>الوصف *</label>
                                <textarea name="discription" class="form-control" rows="3" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>السعر *</label>
                                <input type="number" step="0.01" name="price" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>الكمية *</label>
                                <input type="number" name="amount" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>سنة النشر *</label>
                                <input type="text" name="year_of_publishing" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>رقم الطبعة *</label>
                                <input type="text" name="number_edition" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>صورة الغلاف</label>
                                <input type="file" name="cover_image" class="form-control" accept="image/*">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.books.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_BOOK_ISBN = null;</script>
<script src="{{ asset('js/dashboard/modules/books.js') }}"></script>
@endpush
