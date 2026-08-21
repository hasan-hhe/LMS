@extends('admin.layouts.master')
@section('title', 'الإشعارات')
@section('main-content')
<div class="container">
    <div class="page-inner">
        @include('admin.components.page-header', [
            'title' => 'الإشعارات',
            'arr' => [['title' => 'الإشعارات', 'link' => route('admin.notifications')]],
        ])
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0">قائمة الإشعارات</h4>
                </div>
                <div class="card-body">
                    <h5 id="totalNotifications">العدد: 0</h5>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <input type="text" id="searchNotifications" class="form-control" placeholder="بحث في الإشعارات...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table display table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستلم</th>
                                    <th>العنوان</th>
                                    <th>النص</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody id="notificationsTableBody"></tbody>
                        </table>
                        <div id="notificationsPagination"></div>
                    </div>
                </div>
            </div>

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
                            <select name="user_ids[]" id="notificationUserIds" class="form-control" data-remote="1" multiple></select>
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
@endsection
@push('scripts')
<script src="{{ asset('js/dashboard/modules/notifications.js') }}"></script>
@endpush
