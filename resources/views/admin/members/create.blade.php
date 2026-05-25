@extends('admin.layouts.master')
@section('title', 'إضافة عضو')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إضافة عضو',
            'arr' => [
                ['title' => 'الأعضاء', 'link' => route('admin.members.index')],
                ['title' => 'إضافة', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="memberForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>الاسم الأول *</label>
                                <input type="text" name="first_name" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>الاسم الأخير *</label>
                                <input type="text" name="last_name" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>البريد الإلكتروني *</label>
                                <input type="email" name="email" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>رقم الهاتف *</label>
                                <input type="text" name="phone" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>رقم الهوية *</label>
                                <input type="text" name="identity_number" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>تاريخ انتهاء العضوية</label>
                                <input type="date" name="participe_end_date" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>كلمة المرور *</label>
                                <input type="password" name="password" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>تأكيد كلمة المرور *</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-12 form-group mb-3">
                                <label>العنوان</label>
                                <input type="text" name="adress" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>الصورة الشخصية</label>
                                <input type="file" name="photo_image" class="form-control" accept="image/*">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.members.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_MEMBER_ID = null;</script>
<script src="{{ asset('js/dashboard/modules/members.js') }}"></script>
@endpush
