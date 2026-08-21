(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.borrowingsIndex || '/admin/borrowings';

    const borrowingsFilterConfig = {
        fields: {
            is_returned: '#filterReturned',
        },
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
                let status;
                if (borrowing.is_returned) {
                    status = '<span class="badge bg-success">معاد</span>';
                } else if (borrowing.is_overdue) {
                    status = '<span class="badge bg-danger">متأخر</span>';
                } else {
                    status = '<span class="badge bg-primary">نشط</span>';
                }
                const actions = borrowing.is_returned
                    ? '-'
                    : '<button type="button" class="btn btn-sm btn-success btn-return-borrowing" data-id="' + borrowing.id + '"><i class="fa fa-undo"></i> إعادة</button> ' +
                      '<button type="button" class="btn btn-sm btn-warning btn-extend-borrowing" data-id="' + borrowing.id + '"><i class="fa fa-calendar-plus"></i> تمديد</button> ' +
                      '<button type="button" class="btn btn-sm btn-info btn-admin-extend-borrowing" data-id="' + borrowing.id + '"><i class="fa fa-user-shield"></i> تمديد إداري</button>';
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (borrowing.member?.full_name || '-') + '</td>' +
                    '<td>' + (borrowing.book_instance?.book?.title || '-') + '</td>' +
                    '<td>' + LmsHelpers.formatDate(borrowing.start_date) + '</td>' +
                    '<td>' + LmsHelpers.formatDate(borrowing.end_date) + '</td>' +
                    '<td>' + LmsHelpers.formatDate(borrowing.returned_at) + '</td>' +
                    '<td>' + status + '</td>' +
                    '<td>' + actions + '</td></tr>';
            },
        });
    }

    function promptExtendBorrowing(borrowingId, trigger, administrative) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML =
            '<label class="form-label">تاريخ التمديد الجديد</label>' +
            '<input type="date" id="swalNewEndDate" class="form-control mb-3">' +
            '<p class="text-muted mb-2" id="swalExtensionQuote">جاري حساب النقاط...</p>' +
            '<label class="form-label">السبب (اختياري)</label>' +
            '<input type="text" id="swalExtendCause" class="form-control" placeholder="سبب التمديد">';

        function refreshQuote() {
            const dateInput = document.getElementById('swalNewEndDate');
            const quoteEl = document.getElementById('swalExtensionQuote');
            if (!quoteEl) return;
            const params = dateInput && dateInput.value ? { new_end_date: dateInput.value } : {};
            LmsApi.quoteBorrowingExtension(borrowingId, params).then(function (res) {
                const quote = res.data || {};
                if (administrative) {
                    quoteEl.textContent = 'تمديد إداري بدون خصم نقاط من العضو.';
                    return;
                }
                quoteEl.textContent = 'النقاط المطلوبة: ' + (quote.points ?? 0) +
                    (quote.can_extend ? '' : ' — ' + (quote.reason || 'لا يمكن التمديد الآن'));
            }).catch(function () {
                quoteEl.textContent = 'تعذر حساب تكلفة التمديد';
            });
        }

        swal({
            title: administrative ? 'تمديد إداري' : 'تمديد الاستعارة',
            content: wrapper,
            buttons: {
                cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                confirm: { text: administrative ? 'تمديد إداري' : 'تمديد', className: administrative ? 'btn btn-info' : 'btn btn-warning' },
            },
        }).then(function (confirmed) {
            if (!confirmed) return;

            const newEndDate = document.getElementById('swalNewEndDate').value;
            const cause = document.getElementById('swalExtendCause').value;

            if (!newEndDate) {
                LmsHelpers.notify('error', 'تاريخ التمديد الجديد مطلوب');
                return;
            }

            LmsHelpers.withBusy(trigger, function () {
                return LmsApi.extendBorrowing(borrowingId, {
                    new_end_date: newEndDate,
                    cause: cause || undefined,
                    administrative: administrative ? true : undefined,
                }).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                    loadBorrowingsList(1);
                }).catch(LmsHelpers.handleApiError);
            });
        });

        setTimeout(function () {
            const dateInput = document.getElementById('swalNewEndDate');
            if (dateInput) {
                dateInput.addEventListener('change', refreshQuote);
            }
            refreshQuote();
        }, 0);
    }

    function fillMembersSelect() {
        LmsHelpers.initRemoteSelect('#member_id', {
            placeholder: 'اختر العضو',
            valueKey: 'id',
            labelFn: function (member) {
                return (member.full_name || '') + ' (' + (member.email || '') + ')';
            },
            fetchFn: function (query) {
                return LmsApi.getMembers({ search: query, per_page: 30 }).then(LmsHelpers.extractItems);
            },
        });
        return Promise.resolve();
    }

    function fillAvailableInstancesSelect() {
        const availableStateId = getAvailableStateId();
        LmsHelpers.initRemoteSelect('#book_instance_id', {
            placeholder: 'اختر نسخة الكتاب',
            valueKey: 'id',
            labelFn: function (instance) {
                const title = instance.book?.title || 'نسخة';
                const isbn = instance.book?.isbn ? ' (' + instance.book.isbn + ')' : '';
                const condition = LmsHelpers.conditionLabel(instance.condition);
                const borrowPoints = Number(instance.book?.borrow_points || 0);
                const cost = borrowPoints > 0 ? ' — ' + borrowPoints + ' نقطة للاستعارة' : ' — استعارة مجانية';
                return title + isbn + ' - ' + condition + cost;
            },
            fetchFn: function (query) {
                const params = { search: query, per_page: 30 };
                if (availableStateId) {
                    params.state_id = availableStateId;
                }
                return LmsApi.getBookInstances(params).then(function (res) {
                    let instances = LmsHelpers.extractItems(res);
                    if (!availableStateId) {
                        instances = instances.filter(function (instance) {
                            return instance.state?.state === 'available';
                        });
                    }
                    return instances;
                });
            },
        });
        return Promise.resolve();
    }

    function initBorrowingForm() {
        const form = document.getElementById('borrowingForm');
        if (!form) return;

        Promise.all([
            fillMembersSelect(),
            fillAvailableInstancesSelect(),
        ]).catch(LmsHelpers.handleApiError);

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#borrowingForm');
            const data = LmsHelpers.formToObject(form);

            return LmsApi.createBorrowing(data).then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
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
            LmsHelpers.bindTableFilters(borrowingsFilterConfig, loadBorrowingsList);

            $(document).on('click', '.btn-return-borrowing', function () {
                const btn = this;
                const id = $(this).data('id');
                swal({
                    title: 'إعادة الكتاب',
                    text: 'اختر حالة النسخة عند الإرجاع. الإتلاف أو الفقد يفرض غرامة بقيمة الكتاب.',
                    icon: 'warning',
                    buttons: {
                        cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                        ok: { text: 'سليم', className: 'btn btn-success' },
                        damaged: { text: 'تالف', className: 'btn btn-warning' },
                        lost: { text: 'مفقود', className: 'btn btn-danger' },
                    },
                }).then(function (choice) {
                    if (!choice || choice === 'cancel') return;
                    const outcome = choice === 'damaged' || choice === 'lost' ? choice : 'ok';
                    LmsHelpers.withBusy(btn, function () {
                        return LmsApi.returnBorrowing(id, { outcome: outcome }).then(function (res) {
                            LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                            loadBorrowingsList(1);
                        }).catch(LmsHelpers.handleApiError);
                    });
                });
            });

            $(document).on('click', '.btn-extend-borrowing', function () {
                promptExtendBorrowing($(this).data('id'), this, false);
            });
            $(document).on('click', '.btn-admin-extend-borrowing', function () {
                promptExtendBorrowing($(this).data('id'), this, true);
            });
        }

        initBorrowingForm();
    });
})();
