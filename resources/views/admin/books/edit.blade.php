@extends('admin.layouts.master')
@section('title', 'تعديل كتاب')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'تعديل كتاب',
            'arr' => [
                ['title' => 'الكتب', 'link' => route('admin.books.index')],
                ['title' => 'تعديل', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="bookForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>ISBN</label>
                                <input type="text" class="form-control" value="{{ $isbn }}" disabled>
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
                                <label>السعر (ل.س)</label>
                                <input type="number" step="0.01" name="price" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>سعر النقاط</label>
                                <input type="number" min="0" name="price_points" class="form-control">
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
                                <label>نقاط الاستعارة</label>
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
                            <div class="col-md-3 form-group mb-3">
                                <label>عدد نسخ البيع</label>
                                <input type="number" name="amount" class="form-control" min="0">
                                <small class="form-text text-muted">مخزون الشراء بالنقاط.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label>نسخ الاستعارة الحالية</label>
                                <input type="text" id="current_copies_count" class="form-control" disabled>
                                <small class="form-text text-muted">لإضافة نسخ استعارة أو بيع استخدم صفحات النسخ من القائمة.</small>
                            </div>
                            <div class="col-md-2 form-group mb-3">
                                <label>سنة النشر</label>
                                <input type="text" name="year_of_publishing" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2 form-group mb-3">
                                <label>رقم الطبعة</label>
                                <input type="text" name="number_edition" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>صورة الغلاف</label>
                                <input type="file" name="cover_image" class="form-control" accept="image/*">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3">المحتوى الرقمي</h5>
                        <div class="row" id="digitalAssetForm">
                            <div class="col-md-6 form-group mb-3">
                                <label>ملف PDF</label>
                                <input type="file" id="digital_pdf" class="form-control" accept="application/pdf,.pdf">
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="digital_remove_pdf">
                                    <label class="form-check-label" for="digital_remove_pdf">حذف ملف PDF الحالي</label>
                                </div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>ملف صوتي</label>
                                <input type="file" id="digital_audio" class="form-control" accept="audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/mp4,.mp3,.wav,.ogg,.m4a,.aac">
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="digital_remove_audio">
                                    <label class="form-check-label" for="digital_remove_audio">حذف الملف الصوتي الحالي</label>
                                </div>
                            </div>
                            <div class="col-md-12 form-group mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="digital_is_free">
                                    <label class="form-check-label" for="digital_is_free">مجاني</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex gap-2 mb-3">
                                <button type="button" id="btnSaveDigital" class="btn btn-outline-primary btn-sm">حفظ المحتوى الرقمي</button>
                                <button type="button" id="btnDeleteDigital" class="btn btn-outline-danger btn-sm">حذف المحتوى الرقمي</button>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">تحديث</button>
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
<script>window.LMS_BOOK_ISBN = @json($isbn);</script>
<script src="{{ asset('js/dashboard/modules/books.js') }}"></script>
@endpush
