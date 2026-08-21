(function () {
    'use strict';

    function selectedUserIds() {
        return $('#notificationUserIds').val() || [];
    }

    function toggleAudienceFields() {
        const selected = $('#notificationAudience').val() === 'selected';
        $('#notificationUsersGroup').toggleClass('d-none', !selected);
        $('#notificationUserIds').prop('required', selected);
    }

    function loadMembers(preselectedId) {
        return LmsApi.getMembers({ per_page: 500 }).then(function (res) {
            const $select = $('#notificationUserIds');
            $select.empty();
            (res.data || []).forEach(function (member) {
                const label = member.full_name || member.email || ('عضو #' + member.id);
                $select.append('<option value="' + member.id + '">' + label + '</option>');
            });
            if (preselectedId) {
                $select.val(String(preselectedId));
            }
        });
    }

    function initPage() {
        const form = document.getElementById('sendNotificationForm');
        if (!form) return;

        const params = new URLSearchParams(window.location.search);
        const preselectedId = params.get('user_id');
        if (preselectedId) {
            $('#notificationAudience').val('selected');
        }
        toggleAudienceFields();

        loadMembers(preselectedId).catch(LmsHelpers.handleApiError);

        $('#notificationAudience').on('change', toggleAudienceFields);

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#sendNotificationForm');

            const audience = $('#notificationAudience').val();
            const data = {
                title: form.title.value.trim(),
                body: form.body.value.trim(),
                audience: audience,
                send_email: form.send_email.checked,
            };

            if (audience === 'selected') {
                data.user_ids = selectedUserIds().map(function (id) {
                    return parseInt(id, 10);
                });
            }

            return LmsApi.sendNotification(data).then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res, 'تم إرسال الإشعار'));
                form.reset();
                if (preselectedId) {
                    $('#notificationAudience').val('selected');
                    $('#notificationUserIds').val(String(preselectedId));
                }
                toggleAudienceFields();
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#sendNotificationForm');
            });
        });
    }

    window.runWhenDashboardReady(initPage);
})();
