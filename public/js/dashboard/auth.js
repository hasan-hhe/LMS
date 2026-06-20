(function () {
    'use strict';

    function redirectToLogin() {
        window.location.href = window.LMS_ROUTES?.login || '/admin/login';
    }

    function redirectToDashboard() {
        window.location.href = window.LMS_ROUTES?.dashboard || '/admin';
    }

    window.LmsAuth = {
        initLoginPage() {
            const form = document.getElementById('loginForm');
            if (!form) return;

            if (localStorage.getItem(LmsApi.TOKEN_KEY)) {
                redirectToDashboard();
                return;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                LmsHelpers.clearFormErrors('#loginForm');
                const email = form.email.value;
                const password = form.password.value;
                const submitBtn = form.querySelector('[type="submit"]');
                submitBtn.disabled = true;

                LmsApi.login(email, password).then(function (res) {
                    const user = res.data?.user;
                    const token = res.data?.token;
                    if (!user || !token) {
                        throw new Error('استجابة غير صالحة');
                    }
                    if (!LmsHelpers.isStaff(user)) {
                        LmsHelpers.clearAuth();
                        LmsHelpers.notify('error', 'هذا الحساب غير مصرح له بالدخول للوحة التحكم');
                        return;
                    }
                    LmsHelpers.saveAuth(token, user);
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res, 'تم تسجيل الدخول'));
                    setTimeout(redirectToDashboard, 500);
                }).catch(function (error) {
                    LmsHelpers.handleApiError(error, '#loginForm');
                }).finally(function () {
                    submitBtn.disabled = false;
                });
            });
        },

        initProtectedPage() {
            if (!document.body.dataset.requireAuth && !document.querySelector('[data-require-auth]')) {
                return Promise.resolve();
            }

            const token = localStorage.getItem(LmsApi.TOKEN_KEY);
            if (!token) {
                redirectToLogin();
                return Promise.reject();
            }

            return LmsApi.me().then(function (res) {
                const user = res.data;
                if (!LmsHelpers.isStaff(user)) {
                    LmsHelpers.clearAuth();
                    redirectToLogin();
                    return;
                }
                LmsHelpers.setStoredUser(user);
                LmsHelpers.updateHeaderUser(user);
                LmsHelpers.applyRoleSidebar(user);
            }).catch(function () {
                LmsHelpers.clearAuth();
                redirectToLogin();
            });
        },

        initLogout() {
            $('#logoutBtn').on('click', function (e) {
                e.preventDefault();
                LmsApi.logout().finally(function () {
                    LmsHelpers.clearAuth();
                    redirectToLogin();
                });
            });
        },
    };

    window.LmsAuthReady = Promise.resolve();

    function bootAuth() {
        LmsAuth.initLoginPage();

        if (document.body.dataset.requireAuth === 'true') {
            window.LmsAuthReady = LmsAuth.initProtectedPage().then(function () {
                LmsAuth.initLogout();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootAuth);
    } else {
        bootAuth();
    }
})();
