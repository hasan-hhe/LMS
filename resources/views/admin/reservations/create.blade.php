@extends('admin.layouts.master')
@section('title', 'إضافة حجز')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إضافة حجز',
            'arr' => [
                ['title' => 'الحجوزات', 'link' => route('admin.reservations.index')],
                ['title' => 'إضافة', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="reservationForm">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>المستخدم *</label>
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">اختر المستخدم</option>
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
                            <div class="col-md-12 form-group mb-3">
                                <label>السبب</label>
                                <textarea name="cause" class="form-control" rows="3"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/reservations.js') }}"></script>
@endpush
