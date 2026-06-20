(function () {
    'use strict';

    const ordersIndexUrl = window.LMS_ROUTES?.ordersIndex || '/admin/orders';
    const showBaseUrl = ordersIndexUrl.replace(/\/$/, '');

    const orderStateLabels = {
        pending: 'قيد الانتظار',
        confirmed: 'مؤكد',
        cancelled: 'ملغي',
        rejected: 'مرفوض',
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

    function fillOrderStateSelect(selectedId) {
        const states = window.LMS_LOOKUPS?.orderStates || [];
        LmsHelpers.fillSelect('#orderStateSelect', states, 'id', function (item) {
            return orderStateLabel(item.state);
        }, selectedId);
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
                    '<td>' +
                    '<a href="' + showBaseUrl + '/' + order.id + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>' +
                    '</td></tr>';
            },
        });
    }

    function loadUsersSelect() {
        return LmsApi.getMembers({ per_page: 500 }).then(function (res) {
            const members = res.data || [];
            const $select = $('#user_id');
            $select.find('option:not(:first)').remove();
            members.forEach(function (member) {
                const roleLabel = LmsHelpers.roleLabel(member.role);
                $select.append(
                    '<option value="' + member.id + '">' +
                    (member.full_name || member.email) + ' (' + roleLabel + ')' +
                    '</option>'
                );
            });
        });
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

        form.addEventListener('submit', function (e) {
            e.preventDefault();
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

            LmsApi.createOrder(payload).then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                setTimeout(function () {
                    window.location.href = ordersIndexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#orderForm');
            });
        });
    }

    function initOrderShow() {
        if (!window.LMS_ORDER_ID) return;

        LmsApi.getOrder(window.LMS_ORDER_ID).then(function (res) {
            const order = res.data;
            fillOrderStateSelect(order.state?.id);

            let itemsHtml = '';
            (order.items || []).forEach(function (item, i) {
                itemsHtml += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + (item.book?.isbn || '-') + '</td>' +
                    '<td>' + (item.book?.title || '-') + '</td>' +
                    '<td>' + (item.count || 0) + '</td>' +
                    '<td>' + (item.price_once || 0) + '</td>' +
                    '<td>' + (item.total || 0) + '</td>' +
                    '</tr>';
            });

            $('#orderShowContent').html(
                '<div class="row mb-4">' +
                '<div class="col-md-6"><p><strong>رقم الطلب:</strong> ' + (order.id || '') + '</p></div>' +
                '<div class="col-md-6"><p><strong>المستخدم:</strong> ' + (order.user?.full_name || '-') + '</p></div>' +
                '<div class="col-md-6"><p><strong>الحالة:</strong> ' + orderStateLabel(order.state?.state) + '</p></div>' +
                '<div class="col-md-6"><p><strong>إجمالي الكمية:</strong> ' + (order.total_amount || 0) + '</p></div>' +
                '<div class="col-md-6"><p><strong>إجمالي السعر:</strong> ' + (order.total_prices || 0) + '</p></div>' +
                '</div>' +
                '<h5>عناصر الطلب</h5>' +
                '<div class="table-responsive">' +
                '<table class="table table-striped">' +
                '<thead><tr>' +
                '<th>#</th><th>ISBN</th><th>العنوان</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th>' +
                '</tr></thead>' +
                '<tbody>' + (itemsHtml || '<tr><td colspan="6" class="empty-state">لا توجد عناصر</td></tr>') + '</tbody>' +
                '</table></div>'
            );
        }).catch(function (error) {
            LmsHelpers.handleApiError(error);
        });

        $('#btnUpdateOrderState').on('click', function () {
            const stateId = parseInt($('#orderStateSelect').val(), 10);
            if (!stateId) {
                LmsHelpers.notify('error', 'يرجى اختيار حالة');
                return;
            }

            LmsApi.updateOrderState(window.LMS_ORDER_ID, { state_id: stateId }).then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                initOrderShowReload();
            }).catch(LmsHelpers.handleApiError);
        });
    }

    function initOrderShowReload() {
        LmsApi.getOrder(window.LMS_ORDER_ID).then(function (res) {
            const order = res.data;
            fillOrderStateSelect(order.state?.id);
            $('#orderShowContent').find('p').filter(function () {
                return $(this).text().indexOf('الحالة:') === 0;
            }).html('<strong>الحالة:</strong> ' + orderStateLabel(order.state?.state));
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
