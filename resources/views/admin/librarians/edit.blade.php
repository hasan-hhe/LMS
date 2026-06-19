@extends('admin.layouts.master')
@section('title', 'تعديل أمين مكتبة')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'تعديل أمين مكتبة',
            'arr' => [
                ['title' => 'أمناء المكتبة', 'link' => route('admin.librarians.index')],
                ['title' => 'تعديل', 'link' => ''],
            ],
        ])
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="librarianForm" enctype="multipart/form-data">
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
                                <label>حالة الحساب</label>
                                <select name="state" class="form-control">
                                    <option value="ACTIVE">نشط</option>
                                    <option value="PAUSED">موقوف</option>
                                    <option value="CANCLED">ملغى</option>
                                </select>
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
                                <img id="photoPreview" src="" alt="" class="mt-2 rounded" style="max-height:120px;display:none;">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">تحديث</button>
                            <a href="{{ route('admin.librarians.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.LMS_LIBRARIAN_ID = @json($librarian);</script>
<script src="{{ asset('js/dashboard/modules/librarians.js') }}"></script>
@endpush
