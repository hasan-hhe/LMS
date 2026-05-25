<!DOCTYPE html>
<html lang="ar">

<head>
    <title>تسجيل الدخول — نظام إدارة المكتبة</title>
    @include('admin.layouts.head')
</head>

<body class="bg-gradient-primary" dir="rtl">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8 col-md-9 mt-5">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col">
                                <div class="p-5">
                                    <div class="text-center mb-4">
                                        <h1 class="h4 mb-2">نظام إدارة المكتبة</h1>
                                        <p class="text-muted">تسجيل الدخول للوحة التحكم</p>
                                    </div>
                                    <form id="loginForm">
                                        <div class="form-group mb-3">
                                            <label for="email">البريد الإلكتروني</label>
                                            <input type="email" class="form-control" name="email" id="email"
                                                placeholder="أدخل البريد الإلكتروني" required autocomplete="email" autofocus>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="password">كلمة المرور</label>
                                            <input type="password" class="form-control" name="password" id="password"
                                                placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-block w-100">
                                            تسجيل الدخول
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        window.LMS_ROUTES = {
            login: @json(route('admin.login')),
            dashboard: @json(route('admin.dashboard')),
        };
    </script>
    <script src="{{ asset('js/dashboard/api.js') }}"></script>
    <script src="{{ asset('js/dashboard/helpers.js') }}"></script>
    <script src="{{ asset('js/dashboard/auth.js') }}"></script>
</body>

</html>
