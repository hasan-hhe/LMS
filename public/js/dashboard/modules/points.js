(function () {
    'use strict';

    const memberNames = {};

    function responseItems(res) {
        if (Array.isArray(res?.data)) return res.data;
        if (Array.isArray(res?.data?.data)) return res.data.data;
        return [];
    }

    function responseMeta(res, items) {
        return res?.meta || res?.data?.meta || {
            current_page: 1,
            last_page: 1,
            per_page: items.length,
            total: items.length,
        };
    }

    function codeStatus(code) {
        if (code.used_at || code.is_used) {
            return '<span class="badge bg-secondary">مستخدم</span>';
        }
        if (code.expires_at && new Date(code.expires_at) < new Date()) {
            return '<span class="badge bg-danger">منتهي</span>';
        }
        return '<span class="badge bg-success">متاح</span>';
    }

    function loadTopUpCodes(page) {
        LmsHelpers.showLoading('#topUpCodesTableBody');
        LmsApi.getTopUpCodes({ page: page || 1 }).then(function (res) {
            const codes = responseItems(res);
            const meta = responseMeta(res, codes);
            $('#totalTopUpCodes').text('العدد: ' + (meta.total ?? codes.length));

            if (!codes.length) {
                LmsHelpers.showEmpty('#topUpCodesTableBody');
                $('#topUpCodesPagination').empty();
                return;
            }

            let html = '';
            codes.forEach(function (code, index) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || codes.length) + index + 1;
                const member = code.user || code.member;
                const memberLabel = member?.full_name || member?.email ||
                    (code.user_id ? (memberNames[code.user_id] || ('عضو #' + code.user_id)) : '-');
                html += '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td><code>' + (code.code || '-') + '</code></td>' +
                    '<td>' + (code.points_value ?? code.points ?? 0) + '</td>' +
                    '<td>' + memberLabel + '</td>' +
                    '<td>' + codeStatus(code) + '</td>' +
                    '<td>' + LmsHelpers.formatDate(code.expires_at) + '</td>' +
                    '</tr>';
            });
            $('#topUpCodesTableBody').html(html);
            initDataTable($('#topUpCodesTableBody').closest('table')[0]);
            LmsHelpers.renderPagination(meta, '#topUpCodesPagination', loadTopUpCodes);
        }).catch(function (error) {
            LmsHelpers.handleApiError(error);
            LmsHelpers.showEmpty('#topUpCodesTableBody', 'تعذر تحميل أكواد الشحن');
        });
    }

    function loadMembers() {
        return LmsApi.getMembers({ per_page: 500 }).then(function (res) {
            (res.data || []).forEach(function (member) {
                memberNames[member.id] = member.full_name || member.email || ('عضو #' + member.id);
            });
            LmsHelpers.fillSelect('#topUpCodeUserId', res.data || [], 'id', function (member) {
                return member.full_name || member.email || ('عضو #' + member.id);
            });
        });
    }

    function initTopUpCodesPage() {
        const form = document.getElementById('generateTopUpCodesForm');
        if (!form) return;

        loadMembers().then(function () {
            loadTopUpCodes(1);
        }).catch(function (error) {
            LmsHelpers.handleApiError(error);
            loadTopUpCodes(1);
        });

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#generateTopUpCodesForm');
            const data = LmsHelpers.formToObject(form);
            data.count = parseInt(data.count, 10);
            data.points_value = parseInt(data.points_value, 10);
            if (!data.expires_at) delete data.expires_at;
            if (data.user_id) {
                data.user_id = parseInt(data.user_id, 10);
            } else {
                delete data.user_id;
            }

            return LmsApi.generateTopUpCodes(data).then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res, 'تم توليد أكواد الشحن'));
                form.reset();
                loadTopUpCodes(1);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#generateTopUpCodesForm');
            });
        });
    }

    function initSettingsPage() {
        const form = document.getElementById('pointsSettingsForm');
        if (!form) return;

        LmsApi.getPointsSettings().then(function (res) {
            const settings = res.data || {};
            form.syp_per_point.value = settings.syp_per_point ?? '';
            form.reward_return_on_time.value = settings.reward_return_on_time ?? '';
            if (form.fine_per_day_syp) {
                form.fine_per_day_syp.value = settings.fine_per_day_syp ?? '';
            }
            if (form.fine_per_day_points) {
                form.fine_per_day_points.value = settings.fine_per_day_points ?? '';
            }
            if (form.loan_period_days) {
                form.loan_period_days.value = settings.loan_period_days ?? '';
            }
            if (form.membership_points) {
                form.membership_points.value = settings.membership_points ?? '';
            }
            if (form.membership_days) {
                form.membership_days.value = settings.membership_days ?? '';
            }
        }).catch(LmsHelpers.handleApiError);

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#pointsSettingsForm');
            const data = {
                syp_per_point: Number(form.syp_per_point.value),
                reward_return_on_time: parseInt(form.reward_return_on_time.value, 10),
                fine_per_day_syp: Number(form.fine_per_day_syp.value),
                fine_per_day_points: parseInt(form.fine_per_day_points.value, 10),
            };
            if (form.loan_period_days) {
                data.loan_period_days = parseInt(form.loan_period_days.value, 10);
            }
            if (form.membership_points) {
                data.membership_points = parseInt(form.membership_points.value, 10);
            }
            if (form.membership_days) {
                data.membership_days = parseInt(form.membership_days.value, 10);
            }

            return LmsApi.updatePointsSettings(data).then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res, 'تم حفظ الإعدادات العامة'));
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#pointsSettingsForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        initTopUpCodesPage();
        initSettingsPage();
    });
})();
