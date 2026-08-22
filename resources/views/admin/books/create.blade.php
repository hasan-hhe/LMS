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
                                <select name="auther_id" id="auther_id" class="form-control" data-remote="1" required><option value="">اختر المؤلف</option></select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>التصنيف *</label>
                                <select name="catagory_id" id="catagory_id" class="form-control" data-remote="1" required><option value="">اختر التصنيف</option></select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label>دار النشر *</label>
                                <select name="publisher_id" id="publisher_id" class="form-control" data-remote="1" required><option value="">اختر دار النشر</option></select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-12 form-group mb-3">
                                <label>الوصف *</label>
                                <textarea name="discription" class="form-control" rows="3" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>السعر (ل.س) *</label>
                                <input type="number" step="0.01" name="price" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>سعر النقاط *</label>
                                <input type="number" min="0" name="price_points" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label class="d-block">الاستعارة عليها نقاط؟</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="has_borrow_points" name="has_borrow_points" value="1">
                                    <label class="form-check-label" for="has_borrow_points">نعم، تُخصم نقاط عند الاستعارة</label>
                                </div>
                            </div>
                            <div class="col-md-3 form-group mb-3 d-none" id="borrowPointsWrap">
                                <label>نقاط الاستعارة *</label>
                                <input type="number" min="0" name="borrow_points" class="form-control" value="0">
                                <small class="form-text text-muted">تُخصم من رصيد العضو عند تسجيل الاستعارة.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>مدة الاستعارة (أيام) *</label>
                                <input type="number" min="1" max="365" name="borrow_days" class="form-control" value="14" required>
                                <small class="form-text text-muted">عدد الأيام من التسليم حتى الإرجاع.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2 form-group mb-3">
                                <label>سنة النشر *</label>
                                <input type="text" name="year_of_publishing" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2 form-group mb-3">
                                <label>رقم الطبعة *</label>
                                <input type="text" name="number_edition" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>صورة الغلاف</label>
                                <input type="file" name="cover_image" class="form-control" accept="image/*">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <hr>
                                <h5 class="mb-3">المخزون</h5>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>عدد نسخ البيع *</label>
                                <input type="number" name="amount" class="form-control" required min="0" value="0">
                                <small class="form-text text-muted">مخزون الشراء بالنقاط. يُنقص عند تأكيد الطلب.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>عدد نسخ الاستعارة *</label>
                                <input type="number" name="copies_count" class="form-control" required min="0" value="0">
                                <small class="form-text text-muted">نسخ للتداول في المكتبة. تُنشأ كسجلات مستقلة.</small>
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
