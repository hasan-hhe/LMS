<!DOCTYPE html>
<html lang="ar">
<head>
    <title>استعادة كلمة المرور — نظام إدارة المكتبة</title>
    @include('admin.layouts.head')
</head>
<body class="bg-gradient-primary" dir="rtl">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-8 col-md-9 mt-5">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="p-5">
                        <div class="text-center mb-4">
                            <h1 class="h4 mb-2">استعادة كلمة المرور</h1>
                            <p class="text-muted">أدخل الرمز المرسل إليك وكلمة المرور الجديدة</p>
                        </div>
                        <div id="resetAlert" class="alert d-none" role="alert"></div>
                        <form id="resetPasswordForm">
                            <div class="form-group mb-3">
                                <label for="email">البريد الإلكتروني</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="token">رمز الاستعادة</label>
                                <input type="text" class="form-control" name="token" id="token" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="password">كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" name="password" id="password" required minlength="8">
                            </div>
                            <div class="form-group mb-3">
                                <label for="password_confirmation">تأكيد كلمة المرور</label>
                                <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" required minlength="8">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block w-100">تغيير كلمة المرور</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
(function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('email')) document.getElementById('email').value = params.get('email');
    if (params.get('token')) document.getElementById('token').value = params.get('token');

    document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const alertBox = document.getElementById('resetAlert');
        alertBox.classList.add('d-none');
        axios.post('/api/v1/auth/reset-password', {
            email: document.getElementById('email').value,
            token: document.getElementById('token').value,
            password: document.getElementById('password').value,
            password_confirmation: document.getElementById('password_confirmation').value,
        }).then(function (res) {
            alertBox.className = 'alert alert-success';
            alertBox.textContent = res.data?.body || 'تم تغيير كلمة المرور بنجاح';
            alertBox.classList.remove('d-none');
        }).catch(function (err) {
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = err.response?.data?.body || 'تعذر تغيير كلمة المرور';
            alertBox.classList.remove('d-none');
        });
    });
})();
</script>
</body>
</html>
