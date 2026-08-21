(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.membersIndex || '/admin/members';
    const editBaseUrl = indexUrl.replace(/\/$/, '');

    const membersFilterConfig = {
        search: '#searchMembers',
    };

    function getMembersListParams(page) {
        return LmsHelpers.buildListParams(page, membersFilterConfig);
    }

    function loadMembersList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getMembers,
            getParams: getMembersListParams,
            params: getMembersListParams(page || 1),
            tableBodySelector: '#membersTableBody',
            paginationSelector: '#membersPagination',
            totalSelector: '#totalMembers',
            renderRow: function (member, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (member.full_name || '-') + '</td>' +
                    '<td>' + (member.email || '-') + '</td>' +
                    '<td>' + (member.phone || '-') + '</td>' +
                    '<td>' + (member.identity_number || '-') + '</td>' +
                    '<td>' + LmsHelpers.memberStateBadge(member.state) + '</td>' +
                    '<td>' + LmsHelpers.formatDate(member.participe_end_date) + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + member.id + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a> ' +
                    '<a href="' + editBaseUrl + '/' + member.id + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-member" data-id="' + member.id + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function loadMemberForm(memberId) {
        if (!memberId) return;

        LmsApi.getMember(memberId).then(function (res) {
            const member = res.data;
            const form = document.getElementById('memberForm');
            if (!form || !member) return;

            form.first_name.value = member.first_name || '';
            form.last_name.value = member.last_name || '';
            form.email.value = member.email || '';
            form.phone.value = member.phone || '';
            form.adress.value = member.adress || '';
            form.participe_end_date.value = member.participe_end_date || '';
            if (form.state) {
                form.state.value = member.state || 'ACTIVE';
            }

            if (member.photo_url) {
                $('#photoPreview').attr('src', member.photo_url).show();
            }
        }).catch(LmsHelpers.handleApiError);
    }

    function initMemberForm() {
        const form = document.getElementById('memberForm');
        if (!form) return;

        loadMemberForm(window.LMS_MEMBER_ID);

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#memberForm');
            const formData = LmsHelpers.formToFormData(form);
            const memberId = window.LMS_MEMBER_ID;
            const request = memberId
                ? LmsApi.updateMember(memberId, formData)
                : LmsApi.createMember(formData);

            return request.then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#memberForm');
            });
        });
    }

    function initMemberShow() {
        if (!window.LMS_MEMBER_SHOW || !window.LMS_MEMBER_ID) return;

        LmsApi.getMember(window.LMS_MEMBER_ID).then(function (res) {
            const member = res.data;
            if (member.points_balance !== undefined && member.points_balance !== null) {
                $('#memberPointsBalance').text(member.points_balance);
            }
            $('#memberProfileContent').html(
                '<div class="row">' +
                '<div class="col-md-3">' + (member.photo_url ? '<img src="' + member.photo_url + '" class="img-fluid rounded">' : '') + '</div>' +
                '<div class="col-md-9">' +
                '<h4>' + (member.full_name || '') + '</h4>' +
                '<p><strong>البريد:</strong> ' + (member.email || '-') + '</p>' +
                '<p><strong>الهاتف:</strong> ' + (member.phone || '-') + '</p>' +
                '<p><strong>رقم الهوية:</strong> ' + (member.identity_number || '-') + '</p>' +
                '<p><strong>حالة الحساب:</strong> ' + LmsHelpers.memberStateBadge(member.state) + '</p>' +
                '<p><strong>العنوان:</strong> ' + (member.adress || '-') + '</p>' +
                '<p><strong>انتهاء العضوية:</strong> ' + LmsHelpers.formatDate(member.participe_end_date) + '</p>' +
                '<p><strong>تاريخ التسجيل:</strong> ' + LmsHelpers.formatDate(member.created_at) + '</p>' +
                '</div></div>'
            );
        }).catch(LmsHelpers.handleApiError);

        loadMemberPointsBalance();

        let borrowingsLoaded = false;
        let finesLoaded = false;
        let pointsLoaded = false;

        $('#borrowings-tab').on('shown.bs.tab', function () {
            if (borrowingsLoaded) return;
            borrowingsLoaded = true;
            loadMemberBorrowings(1);
        });

        $('#fines-tab').on('shown.bs.tab', function () {
            if (finesLoaded) return;
            finesLoaded = true;
            loadMemberFines(1);
        });

        $('#points-tab').on('shown.bs.tab', function () {
            if (pointsLoaded) return;
            pointsLoaded = true;
            loadMemberPointsHistory(1);
        });

        $('#btnMemberTopUp').on('click', function () {
            promptMemberTopUp(this);
        });
        $('#btnAdjustMemberPoints').on('click', function () {
            promptMemberPointsAdjustment(this);
        });
    }

    function loadMemberPointsBalance() {
        return LmsApi.getPointsBalance(window.LMS_MEMBER_ID).then(function (res) {
            const balance = res.data?.points_balance ?? res.data?.balance ?? res.data ?? 0;
            $('#memberPointsBalance').text(balance);
        }).catch(function (error) {
            if (error.response?.status !== 404) {
                LmsHelpers.handleApiError(error);
            }
        });
    }

    function promptMemberTopUp(trigger) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML =
            '<label class="form-label">كود الشحن</label>' +
            '<input type="text" id="swalTopUpCode" class="form-control" placeholder="أدخل كود الشحن">';

        swal({
            title: 'شحن رصيد النقاط',
            content: wrapper,
            buttons: {
                cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                confirm: { text: 'شحن', className: 'btn btn-primary' },
            },
        }).then(function (confirmed) {
            if (!confirmed) return;
            const code = document.getElementById('swalTopUpCode').value.trim();
            if (!code) {
                LmsHelpers.notify('error', 'كود الشحن مطلوب');
                return;
            }
            LmsHelpers.withBusy(trigger, function () {
                return LmsApi.topUpPoints({ code: code, member_id: window.LMS_MEMBER_ID }).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res, 'تم شحن رصيد النقاط'));
                    loadMemberPointsBalance();
                    loadMemberPointsHistory(1);
                }).catch(LmsHelpers.handleApiError);
            });
        });
    }

    function promptMemberPointsAdjustment(trigger) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML =
            '<label class="form-label">عدد النقاط (موجب للإضافة وسالب للخصم)</label>' +
            '<input type="number" id="swalAdjustmentPoints" class="form-control mb-3">' +
            '<label class="form-label">ملاحظة *</label>' +
            '<input type="text" id="swalAdjustmentNote" class="form-control" placeholder="سبب التعديل">';

        swal({
            title: 'تعديل رصيد النقاط',
            content: wrapper,
            buttons: {
                cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                confirm: { text: 'حفظ', className: 'btn btn-warning' },
            },
        }).then(function (confirmed) {
            if (!confirmed) return;
            const points = parseInt(document.getElementById('swalAdjustmentPoints').value, 10);
            const note = document.getElementById('swalAdjustmentNote').value.trim();
            if (!Number.isInteger(points) || points === 0 || !note) {
                LmsHelpers.notify('error', 'أدخل عدداً صحيحاً غير صفري مع سبب التعديل');
                return;
            }
            LmsHelpers.withBusy(trigger, function () {
                return LmsApi.adjustPoints({
                    member_id: window.LMS_MEMBER_ID,
                    points: points,
                    note: note,
                }).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res, 'تم تعديل رصيد النقاط'));
                    loadMemberPointsBalance();
                    loadMemberPointsHistory(1);
                }).catch(LmsHelpers.handleApiError);
            });
        });
    }

    function loadMemberPointsHistory(page) {
        const transactionTypes = {
            top_up: 'شحن رصيد',
            adjust: 'تعديل إداري',
            borrowing: 'استعارة',
            fine: 'دفع غرامة',
            order: 'شراء',
            reward: 'مكافأة',
        };
        LmsHelpers.loadPaginatedTable({
            apiCall: function (params) {
                return LmsApi.getPointsHistory(window.LMS_MEMBER_ID, params).then(function (res) {
                    if (Array.isArray(res.data?.data)) {
                        return { data: res.data.data, meta: res.data.meta || res.meta };
                    }
                    return res;
                });
            },
            params: { page: page || 1 },
            tableBodySelector: '#memberPointsHistoryBody',
            paginationSelector: '#memberPointsHistoryPagination',
            renderRow: function (transaction, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const points = transaction.points ?? transaction.amount ?? 0;
                const pointsClass = points >= 0 ? 'text-success' : 'text-danger';
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td class="' + pointsClass + ' fw-bold">' + (points > 0 ? '+' : '') + points + '</td>' +
                    '<td>' + (transactionTypes[transaction.type] || transaction.type || '-') + '</td>' +
                    '<td>' + (transaction.note || transaction.description || transaction.type || '-') + '</td>' +
                    '<td>' + LmsHelpers.formatDate(transaction.created_at) + '</td>' +
                    '</tr>';
            },
        });
    }

    function loadMemberBorrowings(page) {
        LmsHelpers.loadPaginatedTable({
            apiCall: function (params) {
                return LmsApi.getMemberBorrowings(window.LMS_MEMBER_ID, params);
            },
            params: { page: page || 1 },
            tableBodySelector: '#memberBorrowingsBody',
            paginationSelector: '#memberBorrowingsPagination',
            renderRow: function (borrowing, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const status = borrowing.is_returned
                    ? '<span class="badge bg-success">مُعاد</span>'
                    : (borrowing.is_overdue
                        ? '<span class="badge bg-danger">متأخر</span>'
                        : '<span class="badge bg-primary">نشط</span>');
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (borrowing.book_instance?.book?.title || '-') + '</td>' +
                    '<td>' + LmsHelpers.formatDate(borrowing.start_date) + '</td>' +
                    '<td>' + LmsHelpers.formatDate(borrowing.end_date) + '</td>' +
                    '<td>' + status + '</td>' +
                    '</tr>';
            },
        });
    }

    function loadMemberFines(page) {
        LmsHelpers.loadPaginatedTable({
            apiCall: function (params) {
                return LmsApi.getMemberFines(window.LMS_MEMBER_ID, params);
            },
            params: { page: page || 1 },
            tableBodySelector: '#memberFinesBody',
            paginationSelector: '#memberFinesPagination',
            renderRow: function (fine, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const status = fine.is_paid
                    ? '<span class="badge bg-success">مدفوعة</span>'
                    : '<span class="badge bg-warning text-dark">غير مدفوعة</span>';
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (fine.days_late || 0) + '</td>' +
                    '<td>' + (fine.fine || 0) + '</td>' +
                    '<td>' + status + '</td>' +
                    '<td>' + LmsHelpers.formatDate(fine.paid_at) + '</td>' +
                    '</tr>';
            },
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('membersTableBody')) {
            loadMembersList(1);
            LmsHelpers.bindTableFilters(membersFilterConfig, loadMembersList);

            $(document).on('click', '.btn-delete-member', function () {
                const id = $(this).data('id');
                confirmDelete('هل أنت متأكد من حذف هذا العضو؟', function () {
                    LmsApi.deleteMember(id).then(function (res) {
                        LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                        loadMembersList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initMemberForm();
        initMemberShow();
    });
})();
