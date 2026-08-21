@extends('admin.layouts.master')
@section('title', 'إضافة محتوى رقمي')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إضافة محتوى رقمي',
            'arr' => [
                ['title' => 'المحتوى الرقمي', 'link' => route('admin.digital-assets.index')],
                ['title' => 'إضافة', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="digitalAssetPageForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-12 form-group mb-3">
                                <label>الكتاب *</label>
                                <select name="book_ISBN" id="book_ISBN" class="form-control" data-remote="1" required>
                                    <option value="">اختر الكتاب</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>ملف PDF</label>
                                <input type="file" name="pdf" id="digital_pdf" class="form-control" accept="application/pdf,.pdf">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>ملف صوتي</label>
                                <input type="file" name="audio" id="digital_audio" class="form-control" accept="audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/mp4,.mp3,.wav,.ogg,.m4a,.aac">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-12 form-group mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="digital_is_free">
                                    <label class="form-check-label" for="digital_is_free">مجاني</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.digital-assets.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_DIGITAL_ISBN = null;</script>
<script src="{{ asset('js/dashboard/modules/digital-assets.js') }}"></script>
@endpush
