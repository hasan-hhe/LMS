@extends('admin.layouts.master')
@section('title', 'إضافة نسخ بيع')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إضافة نسخ بيع',
            'arr' => [
                ['title' => 'نسخ الكتب البيع', 'link' => route('admin.sale-copies.index')],
                ['title' => 'إضافة', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">تُضاف الكمية إلى مخزون البيع الحالي للكتاب، ولا تُنشئ نسخ استعارة.</p>
                    <form id="saleCopiesForm">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>الكتاب *</label>
                                <select name="book_ISBN" id="book_ISBN" class="form-control" data-remote="1" required>
                                    <option value="">اختر الكتاب</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>عدد نسخ البيع المراد إضافتها *</label>
                                <input type="number" name="copies_count" class="form-control" min="1" max="500" value="1" required>
                                <small class="form-text text-muted">تُضاف إلى الكمية الحالية.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.sale-copies.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/sale-copies.js') }}"></script>
@endpush
