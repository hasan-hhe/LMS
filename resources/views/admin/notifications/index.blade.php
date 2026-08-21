@extends('admin.layouts.master')
@section('title', 'إرسال إشعار')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'إرسال إشعار',
            'arr' => [['title' => 'الإشعارات', 'link' => route('admin.notifications')]],
        ])
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">إشعار للمستخدمين</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">يصل الإشعار فوراً إلى تطبيق المستخدم عبر Ably، ويُحفظ في قائمة إشعاراته.</p>
                        <form id="sendNotificationForm">
                            <div class="form-group mb-3">
                                <label>جهة الإرسال *</label>
                                <select name="audience" id="notificationAudience" class="form-control" required>
                                    <option value="members">كل الأعضاء</option>
                                    <option value="selected">أعضاء محددون</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3 d-none" id="notificationUsersGroup">
                                <label>المستلمون *</label>
                                <select name="user_ids[]" id="notificationUserIds" class="form-control" multiple size="8"></select>
                                <small class="text-muted">اضغط Ctrl لاختيار أكثر من عضو.</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>العنوان *</label>
                                <input type="text" name="title" class="form-control" maxlength="150" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label>النص *</label>
                                <textarea name="body" class="form-control" rows="5" maxlength="2000" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-check mb-4">
                                <input type="checkbox" name="send_email" id="notificationSendEmail" class="form-check-input" value="1">
                                <label class="form-check-label" for="notificationSendEmail">إرسال نسخة بالبريد أيضاً</label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> إرسال الإشعار
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/notifications.js') }}"></script>
@endpush
