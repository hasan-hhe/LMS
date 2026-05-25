@extends('admin.layouts.master')
@section('title', 'تفاصيل الكتاب')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'تفاصيل الكتاب',
            'arr' => [
                ['title' => 'الكتب', 'link' => route('admin.books.index')],
                ['title' => 'تفاصيل', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" id="bookShowContent">
                    <div class="page-loading"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.books.edit', $isbn) }}" class="btn btn-primary">تعديل</a>
                    <a href="{{ route('admin.books.index') }}" class="btn btn-secondary">رجوع</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_BOOK_ISBN = @json($isbn); window.LMS_BOOK_SHOW = true;</script>
<script src="{{ asset('js/dashboard/modules/books.js') }}"></script>
@endpush
