(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.reservationsIndex || '/admin/reservations';

    function reservationStateLabel(state) {
        const map = {
            pending: 'قيد الانتظار',
            fulfilled: 'مكتمل',
            cancelled: 'ملغى',
            expired: 'منتهي',
        };
        return map[state] || state || '-';
    }

    function loadReservationsList(params) {
        params = params || { page: 1 };
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getReservations,
            params: params,
            tableBodySelector: '#reservationsTableBody',
            paginationSelector: '#reservationsPagination',
            totalSelector: '#totalReservations',
            renderRow: function (reservation, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const state = reservation.state?.state;
                const canCancel = state === 'pending';
                const cancelButton = canCancel
                    ? '<button type="button" class="btn btn-sm btn-danger btn-cancel-reservation" data-id="' + reservation.id + '"><i class="fa fa-times"></i> إلغاء</button>'
                    : '';
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (reservation.user?.full_name || '-') + '</td>' +
                    '<td>' + (reservation.book_instance?.book?.title || '-') + '</td>' +
                    '<td>' + (reservation.cause || '-') + '</td>' +
                    '<td>' + reservationStateLabel(state) + '</td>' +
                    '<td>' + LmsHelpers.formatDate(reservation.reserved_at) + '</td>' +
                    '<td>' + cancelButton + '</td>' +
                    '</tr>';
            },
        });
    }

    function fillUsersSelect() {
        return LmsApi.getMembers({ per_page: 200 }).then(function (res) {
            LmsHelpers.fillSelect('#user_id', res.data, 'id', function (member) {
                return (member.full_name || '') + ' (' + (member.email || '') + ')';
            });
        });
    }

    function fillBookInstancesSelect() {
        return LmsApi.getBookInstances({ per_page: 200 }).then(function (res) {
            LmsHelpers.fillSelect('#book_instance_id', res.data, 'id', function (instance) {
                const title = instance.book?.title || 'نسخة';
                const isbn = instance.book?.isbn ? ' (' + instance.book.isbn + ')' : '';
                const stateLabel = instance.state?.state || '-';
                return title + isbn + ' - ' + stateLabel;
            });
        });
    }

    function initReservationForm() {
        const form = document.getElementById('reservationForm');
        if (!form) return;

        Promise.all([
            fillUsersSelect(),
            fillBookInstancesSelect(),
        ]).catch(LmsHelpers.handleApiError);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            LmsHelpers.clearFormErrors('#reservationForm');
            const data = LmsHelpers.formToObject(form);

            LmsApi.createReservation(data).then(function (res) {
                LmsHelpers.notify('success', res.message);
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#reservationForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('reservationsTableBody')) {
            loadReservationsList({ page: 1 });

            $(document).on('click', '.btn-cancel-reservation', function () {
                const id = $(this).data('id');
                confirmDelete('هل أنت متأكد من إلغاء هذا الحجز؟', function () {
                    LmsApi.cancelReservation(id).then(function (res) {
                        LmsHelpers.notify('success', res.message);
                        loadReservationsList({ page: 1 });
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initReservationForm();
    });
})();
