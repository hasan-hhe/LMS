(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.borrowingsIndex || '/admin/borrowings';

    const borrowingsFilterConfig = {
        extra: { is_returned: 'false' },
    };

    function getBorrowingsListParams(page) {
        return LmsHelpers.buildListParams(page, borrowingsFilterConfig);
    }

    function getAvailableStateId() {
        const states = window.LMS_LOOKUPS?.instanceStates || [];
        const available = states.find(function (state) {
            return state.state === 'available';
        });
        return available ? available.id : null;
    }

    function loadBorrowingsList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getBorrowings,
            getParams: getBorrowingsListParams,
            params: getBorrowingsListParams(page || 1),
            tableBodySelector: '#borrowingsTableBody',
            paginationSelector: '#borrowingsPagination',
            totalSelector: '#totalBorrowings',
            renderRow: function (borrowing, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const status = borrowing.is_overdue
                    ? '<span class="badge bg-danger">متأخر</span>'
                    : '<span class="badge bg-primary">نشط</span>';
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (borrowing.member?.full_name || '-') + '</td>' +
                    '<td>' + (borrowing.book_instance?.book?.title || '-') + '</td>' +
                    '<td>' + LmsHelpers.formatDate(borrowing.start_date) + '</td>' +
                    '<td>' + LmsHelpers.formatDate(borrowing.end_date) + '</td>' +
                    '<td>' + status + '</td>' +
                    '<td>' +
                    '<button type="button" class="btn btn-sm btn-success btn-return-borrowing" data-id="' + borrowing.id + '"><i class="fa fa-undo"></i> إعادة</button> ' +
                    '<button type="button" class="btn btn-sm btn-warning btn-extend-borrowing" data-id="' + borrowing.id + '"><i class="fa fa-calendar-plus"></i> تمديد</button>' +
                    '</td></tr>';
            },
        });
    }

    function promptExtendBorrowing(borrowingId) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML =
            '<label class="form-label">تاريخ التمديد الجديد</label>' +
            '<input type="date" id="swalNewEndDate" class="form-control mb-3">' +
            '<label class="form-label">السبب (اختياري)</label>' +
            '<input type="text" id="swalExtendCause" class="form-control" placeholder="سبب التمديد">';

        swal({
            title: 'تمديد الاستعارة',
            content: wrapper,
            buttons: {
                cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                confirm: { text: 'تمديد', className: 'btn btn-warning' },
            },
        }).then(function (confirmed) {
            if (!confirmed) return;

            const newEndDate = document.getElementById('swalNewEndDate').value;
            const cause = document.getElementById('swalExtendCause').value;

            if (!newEndDate) {
                LmsHelpers.notify('error', 'تاريخ التمديد الجديد مطلوب');
                return;
            }

            LmsApi.extendBorrowing(borrowingId, {
                new_end_date: newEndDate,
                cause: cause || undefined,
            }).then(function (res) {
                LmsHelpers.notify('success', res.message);
                loadBorrowingsList(1);
            }).catch(LmsHelpers.handleApiError);
        });
    }

    function fillMembersSelect() {
        return LmsApi.getMembers({ per_page: 200 }).then(function (res) {
            LmsHelpers.fillSelect('#member_id', res.data, 'id', function (member) {
                return (member.full_name || '') + ' (' + (member.email || '') + ')';
            });
        });
    }

    function fillAvailableInstancesSelect() {
        const params = { per_page: 200 };
        const availableStateId = getAvailableStateId();
        if (availableStateId) {
            params.state_id = availableStateId;
        }

        return LmsApi.getBookInstances(params).then(function (res) {
            let instances = res.data || [];
            if (!availableStateId) {
                instances = instances.filter(function (instance) {
                    return instance.state?.state === 'available';
                });
            }

            LmsHelpers.fillSelect('#book_instance_id', instances, 'id', function (instance) {
                const title = instance.book?.title || 'نسخة';
                const isbn = instance.book?.isbn ? ' (' + instance.book.isbn + ')' : '';
                const condition = LmsHelpers.conditionLabel(instance.condition);
                return title + isbn + ' - ' + condition;
            });
        });
    }

    function initBorrowingForm() {
        const form = document.getElementById('borrowingForm');
        if (!form) return;

        Promise.all([
            fillMembersSelect(),
            fillAvailableInstancesSelect(),
        ]).catch(LmsHelpers.handleApiError);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            LmsHelpers.clearFormErrors('#borrowingForm');
            const data = LmsHelpers.formToObject(form);

            LmsApi.createBorrowing(data).then(function (res) {
                LmsHelpers.notify('success', res.message);
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#borrowingForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('borrowingsTableBody')) {
            loadBorrowingsList(1);

            $(document).on('click', '.btn-return-borrowing', function () {
                const id = $(this).data('id');
                swal('هل أنت متأكد من إعادة هذا الكتاب؟', {
                    icon: 'warning',
                    buttons: {
                        cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                        confirm: { text: 'إعادة', className: 'btn btn-success' },
                    },
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    LmsApi.returnBorrowing(id).then(function (res) {
                        LmsHelpers.notify('success', res.message);
                        loadBorrowingsList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });

            $(document).on('click', '.btn-extend-borrowing', function () {
                promptExtendBorrowing($(this).data('id'));
            });
        }

        initBorrowingForm();
    });
})();
