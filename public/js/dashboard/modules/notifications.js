(function () {
    'use strict';

    const filterConfig = {
        search: '#searchNotifications',
    };

    function selectedUserIds() {
        return $('#notificationUserIds').val() || [];
    }

    function toggleAudienceFields() {
        const selected = $('#notificationAudience').val() === 'selected';
        $('#notificationUsersGroup').toggleClass('d-none', !selected);
        $('#notificationUserIds').prop('required', selected);
    }

    function loadMembers(preselectedId) {
        LmsHelpers.initRemoteSelect('#notificationUserIds', {
            placeholder: 'ابحث عن عضو...',
            valueKey: 'id',
            labelFn: function (member) {
                return member.full_name || member.email || ('عضو #' + member.id);
            },
            selectedValue: preselectedId,
            fetchFn: function (query) {
                return LmsApi.getMembers({ search: query, per_page: 30 }).then(LmsHelpers.extractItems);
            },
        });
        return Promise.resolve();
    }

    function loadNotificationsList(page) {
        if (!document.getElementById('notificationsTableBody')) return Promise.resolve();
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getNotifications,
            getParams: function (nextPage) {
                return LmsHelpers.buildListParams(nextPage, filterConfig);
            },
            params: LmsHelpers.buildListParams(page || 1, filterConfig),
            tableBodySelector: '#notificationsTableBody',
            paginationSelector: '#notificationsPagination',
            totalSelector: '#totalNotifications',
            renderRow: function (item, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (item.user?.full_name || item.user?.email || '-') + '</td>' +
                    '<td>' + (item.title || '-') + '</td>' +
                    '<td>' + (item.message || '-') + '</td>' +
                    '<td>' + (item.read_at ? 'مقروء' : 'غير مقروء') + '</td>' +
                    '<td>' + LmsHelpers.formatDate(item.created_at) + '</td>' +
                    '</tr>';
            },
        });
    }

    function initPage() {
        const form = document.getElementById('sendNotificationForm');
        if (document.getElementById('notificationsTableBody')) {
            loadNotificationsList(1);
            LmsHelpers.bindTableFilters(filterConfig, loadNotificationsList);
        }
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
                }
                toggleAudienceFields();
                loadNotificationsList(1);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#sendNotificationForm');
            });
        });
    }

    window.runWhenDashboardReady(initPage);
})();
