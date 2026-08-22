(function () {
    'use strict';

    const ordersIndexUrl = window.LMS_ROUTES?.ordersIndex || '/admin/orders';
    const showBaseUrl = ordersIndexUrl.replace(/\/$/, '');

    const orderStateLabels = {
        pending: 'قيد الانتظار',
        confirmed: 'مؤكد — بانتظار الاستلام',
        delivered: 'تم التسليم',
        cancelled: 'ملغي',
        rejected: 'مرفوض',
    };

    const orderTransitions = {
        pending: ['confirmed', 'cancelled', 'rejected'],
        confirmed: ['delivered', 'cancelled', 'rejected'],
        delivered: [],
        cancelled: [],
        rejected: [],
    };

    const ordersFilterConfig = {
        fields: {
            state_id: '#filterOrderState',
        },
    };

    function getOrdersListParams(page) {
        return LmsHelpers.buildListParams(page, ordersFilterConfig);
    }

    function orderStateLabel(state) {
        return orderStateLabels[state] || state || '-';
    }

    function fillOrderStateFilter() {
        const states = window.LMS_LOOKUPS?.orderStates || [];
        LmsHelpers.fillSelect('#filterOrderState', states, 'id', function (item) {
            return orderStateLabel(item.state);
        });
    }

    function orderStatesFromLookups() {
        const states = window.LMS_LOOKUPS?.orderStates;
        if (Array.isArray(states)) {
            return states;
        }
        if (states && typeof states === 'object') {
            return Object.values(states);
        }
        return [];
    }

    function allowedOrderStates(currentKey) {
        const allStates = orderStatesFromLookups();
        const allowedKeys = [currentKey].concat(orderTransitions[currentKey] || []);
        const filtered = allStates.filter(function (item) {
            return allowedKeys.indexOf(item.state) !== -1;
        });
        return filtered.length ? filtered : allStates;
    }

    function fillOrderStateSelect(order) {
        const $select = $('#orderStateSelect');
        if (!$select.length) return;

        LmsHelpers.fillSelect(
            '#orderStateSelect',
            allowedOrderStates(order?.state?.state),
            'id',
            function (item) {
                return orderStateLabel(item.state);
            },
            order?.state?.id
        );
        toggleOrderReasonField();
    }

    function selectedOrderStateKey() {
        const selectedId = $('#orderStateSelect').val();
        const found = orderStatesFromLookups().find(function (item) {
            return String(item.id) === String(selectedId);
        });
        return found?.state || '';
    }

    function setOrderStateSelectEnabled(enabled) {
        const choices = LmsHelpers.getChoicesInstance('#orderStateSelect');
        if (choices) {
            if (enabled) {
                choices.enable();
            } else {
                choices.disable();
            }
            return;
        }
        $('#orderStateSelect').prop('disabled', !enabled);
    }

    function toggleOrderReasonField() {
        const needsReason = ['cancelled', 'rejected'].indexOf(selectedOrderStateKey()) !== -1;
        $('#orderStateReasonWrap').toggleClass('d-none', !needsReason);
        if (!needsReason) {
            $('#orderStateReason').val('');
        }
    }

    function renderOrderShow(order) {
        fillOrderStateSelect(order);

        let itemsHtml = '';
        (order.items || []).forEach(function (item, i) {
            const formatLabel = item.format === 'pdf' ? 'PDF' : 'ورقي (نسخ البيع)';
            itemsHtml += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + (item.book?.isbn || '-') + '</td>' +
                '<td>' + (item.book?.title || '-') + '</td>' +
                '<td>' + formatLabel + '</td>' +
                '<td>' + (item.count || 0) + '</td>' +
                '<td>' + (item.price_once || 0) + '</td>' +
                '<td>' + (item.total || 0) + '</td>' +
                '</tr>';
        });

        const terminal = ['cancelled', 'rejected'].indexOf(order.state?.state) !== -1;
        $('#orderShowContent').html(
            '<div class="row mb-4">' +
            '<div class="col-md-6"><p><strong>رقم الطلب:</strong> ' + (order.id || '') + '</p></div>' +
            '<div class="col-md-6"><p><strong>المستخدم:</strong> ' + (order.user?.full_name || '-') + '</p></div>' +
            '<div class="col-md-6"><p><strong>الحالة:</strong> ' + orderStateLabel(order.state?.state) + '</p></div>' +
            '<div class="col-md-6"><p><strong>إجمالي الكمية:</strong> ' + (order.total_amount || 0) + '</p></div>' +
            '<div class="col-md-6"><p><strong>إجمالي السعر:</strong> ' + (order.total_prices || 0) + '</p></div>' +
            '<div class="col-md-6"><p><strong>إجمالي النقاط:</strong> ' + (order.total_points ?? 0) + ' نقطة</p></div>' +
            (order.pickup_expires_at
                ? '<div class="col-md-6"><p><strong>آخر موعد استلام:</strong> ' + LmsHelpers.formatDate(order.pickup_expires_at) + '</p></div>'
                : '') +
            (order.reason
                ? '<div class="col-12"><p><strong>سبب الرفض/الإلغاء:</strong> ' + escapeHtml(order.reason) + '</p></div>'
                : '') +
            (order.state?.state === 'confirmed'
                ? '<div class="col-12"><p class="text-muted">الرفض أو الإلغاء يعيد النقاط ومخزون البيع. إن لم يُستلم خلال 48 ساعة يُلغى تلقائياً.</p></div>'
                : '') +
            '</div>' +
            '<h5>عناصر الطلب</h5>' +
            '<div class="table-responsive">' +
            '<table class="table table-striped">' +
            '<thead><tr>' +
            '<th>#</th><th>ISBN</th><th>العنوان</th><th>النوع</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th>' +
            '</tr></thead>' +
            '<tbody>' + (itemsHtml || '<tr><td colspan="7" class="empty-state">لا توجد عناصر</td></tr>') + '</tbody>' +
            '</table></div>'
        );

        $('#btnUpdateOrderState').prop('disabled', terminal);
        setOrderStateSelectEnabled(!terminal);
    }

    function loadOrdersList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getOrders,
            getParams: getOrdersListParams,
            params: getOrdersListParams(page || 1),
            tableBodySelector: '#ordersTableBody',
            paginationSelector: '#ordersPagination',
            totalSelector: '#totalOrders',
            renderRow: function (order, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (order.id || '-') + '</td>' +
                    '<td>' + (order.user?.full_name || '-') + '</td>' +
                    '<td>' + orderStateLabel(order.state?.state) + '</td>' +
                    '<td>' + (order.total_amount || 0) + '</td>' +
                    '<td>' + (order.total_prices || 0) + '</td>' +
                    '<td>' + (order.total_points ?? 0) + '</td>' +
                    '<td>' + (order.pickup_expires_at ? LmsHelpers.formatDate(order.pickup_expires_at) : '-') + '</td>' +
                    '<td>' +
                    '<a href="' + showBaseUrl + '/' + order.id + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>' +
                    '</td></tr>';
            },
        });
    }

    function loadUsersSelect() {
        LmsHelpers.initRemoteSelect('#user_id', {
            placeholder: 'اختر المستخدم...',
            valueKey: 'id',
            labelFn: function (member) {
                return (member.full_name || member.email) + ' (' + LmsHelpers.roleLabel(member.role) + ')';
            },
            fetchFn: function (query) {
                return LmsApi.getMembers({ search: query, per_page: 30 }).then(LmsHelpers.extractItems);
            },
        });
        return Promise.resolve();
    }

    let itemRowIndex = 0;

    function buildOrderItemRow(index) {
        return '<div class="order-item-row row mb-2 g-2 align-items-end" data-index="' + index + '">' +
            '<div class="col-md-5 form-group">' +
            '<label class="small text-muted">ISBN *</label>' +
            '<input type="text" class="form-control item-isbn" placeholder="رقم ISBN" required>' +
            '</div>' +
            '<div class="col-md-3 form-group">' +
            '<label class="small text-muted">الكمية *</label>' +
            '<input type="number" class="form-control item-count" min="1" value="1" required>' +
            '</div>' +
            '<div class="col-md-2">' +
            '<button type="button" class="btn btn-danger btn-remove-item w-100"><i class="fa fa-trash"></i></button>' +
            '</div>' +
            '</div>';
    }

    function initOrderItems() {
        const $container = $('#orderItemsContainer');
        if (!$container.length) return;

        itemRowIndex = 0;
        $container.html(buildOrderItemRow(itemRowIndex++));

        $('#btnAddOrderItem').on('click', function () {
            $container.append(buildOrderItemRow(itemRowIndex++));
        });

        $container.on('click', '.btn-remove-item', function () {
            if ($container.find('.order-item-row').length <= 1) {
                LmsHelpers.notify('error', 'يجب أن يحتوي الطلب على عنصر واحد على الأقل');
                return;
            }
            $(this).closest('.order-item-row').remove();
        });
    }

    function collectOrderItems() {
        const items = [];
        $('#orderItemsContainer .order-item-row').each(function () {
            const isbn = $(this).find('.item-isbn').val()?.trim();
            const count = parseInt($(this).find('.item-count').val(), 10);
            if (isbn && count >= 1) {
                items.push({ isbn: isbn, count: count });
            }
        });
        return items;
    }

    function initOrderForm() {
        const form = document.getElementById('orderForm');
        if (!form) return;

        initOrderItems();
        loadUsersSelect();

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#orderForm');

            const items = collectOrderItems();
            if (!items.length) {
                LmsHelpers.notify('error', 'يجب إضافة عنصر واحد على الأقل');
                return;
            }

            const payload = {
                user_id: parseInt(form.user_id.value, 10),
                items: items,
            };

            return LmsApi.createOrder(payload).then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                setTimeout(function () {
                    window.location.href = ordersIndexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#orderForm');
            });
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initOrderShow() {
        if (!window.LMS_ORDER_ID) return;

        loadOrderShow();

        $('#orderStateSelect').on('change', toggleOrderReasonField);

        $('#btnUpdateOrderState').on('click', function () {
            const btn = this;
            const stateId = parseInt($('#orderStateSelect').val(), 10);
            const stateKey = selectedOrderStateKey();
            if (!stateId) {
                LmsHelpers.notify('error', 'يرجى اختيار حالة');
                return;
            }

            const payload = { state_id: stateId };
            if (['cancelled', 'rejected'].indexOf(stateKey) !== -1) {
                const reason = ($('#orderStateReason').val() || '').trim();
                if (reason.length < 3) {
                    LmsHelpers.notify('error', 'يجب كتابة سبب الرفض أو الإلغاء');
                    return;
                }
                payload.reason = reason;
            }

            LmsHelpers.withBusy(btn, function () {
                return LmsApi.updateOrderState(window.LMS_ORDER_ID, payload).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                    $('#orderStateReason').val('');
                    loadOrderShow();
                }).catch(LmsHelpers.handleApiError);
            });
        });
    }

    function loadOrderShow() {
        const lookups = orderStatesFromLookups();
        const statesPromise = lookups.length
            ? Promise.resolve(lookups)
            : LmsApi.getOrderStates().then(function (res) {
                const items = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
                window.LMS_LOOKUPS = window.LMS_LOOKUPS || {};
                window.LMS_LOOKUPS.orderStates = items;
                return items;
            }).catch(function () {
                return [];
            });

        return Promise.all([
            LmsApi.getOrder(window.LMS_ORDER_ID),
            statesPromise,
        ]).then(function (results) {
            renderOrderShow(results[0].data);
        }).catch(function (error) {
            LmsHelpers.handleApiError(error);
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('ordersTableBody')) {
            fillOrderStateFilter();
            loadOrdersList(1);
            LmsHelpers.bindTableFilters(ordersFilterConfig, loadOrdersList);
        }

        initOrderForm();
        initOrderShow();
    });
})();
