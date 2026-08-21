(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.saleCopiesIndex || '/admin/sale-copies';

    const saleCopiesFilterConfig = {
        search: '#searchSaleCopies',
    };

    function itemsFrom(res) {
        return LmsHelpers.extractItems(res);
    }

    function getSaleCopiesListParams(page) {
        return LmsHelpers.buildListParams(page, saleCopiesFilterConfig);
    }

    function loadSaleCopiesList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getBooks,
            getParams: getSaleCopiesListParams,
            params: getSaleCopiesListParams(page || 1),
            tableBodySelector: '#saleCopiesTableBody',
            paginationSelector: '#saleCopiesPagination',
            totalSelector: '#totalSaleCopies',
            renderRow: function (book, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const stock = book.sale_stock ?? book.amount ?? 0;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (book.isbn || '-') + '</td>' +
                    '<td>' + (book.title || '-') + '</td>' +
                    '<td>' + (book.author?.full_name || '-') + '</td>' +
                    '<td><span class="badge bg-primary">' + stock + '</span></td>' +
                    '<td>' + (book.price_points ?? 0) + '</td>' +
                    '<td>' +
                    '<button type="button" class="btn btn-sm btn-warning btn-set-sale-stock" data-isbn="' + book.isbn + '" data-stock="' + stock + '"><i class="fa fa-edit"></i> تعديل الكمية</button>' +
                    '</td></tr>';
            },
        });
    }

    function promptSetSaleStock(isbn, currentStock, trigger) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML =
            '<label class="form-label">عدد نسخ البيع</label>' +
            '<input type="number" id="swalSaleStock" class="form-control" min="0" value="' + (currentStock || 0) + '">';

        swal({
            title: 'تعديل مخزون البيع',
            content: wrapper,
            buttons: {
                cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                confirm: { text: 'حفظ', className: 'btn btn-warning' },
            },
        }).then(function (confirmed) {
            if (!confirmed) return;

            const amount = parseInt(document.getElementById('swalSaleStock').value, 10);
            if (Number.isNaN(amount) || amount < 0) {
                LmsHelpers.notify('error', 'عدد نسخ البيع غير صالح');
                return;
            }

            LmsHelpers.withBusy(trigger, function () {
                return LmsApi.updateBook(isbn, { amount: amount }).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                    loadSaleCopiesList(1);
                }).catch(LmsHelpers.handleApiError);
            });
        });
    }

    function initSaleCopiesForm() {
        const form = document.getElementById('saleCopiesForm');
        if (!form) return;

        LmsHelpers.initRemoteSelect('#book_ISBN', {
            placeholder: 'اختر الكتاب',
            valueKey: 'isbn',
            labelFn: function (book) {
                return (book.title || '') + ' (' + (book.isbn || '') + ') — البيع: ' + (book.sale_stock ?? book.amount ?? 0);
            },
            fetchFn: function (query) {
                return LmsApi.getBooks({ search: query, per_page: 30 }).then(itemsFrom);
            },
        });

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#saleCopiesForm');
            const data = LmsHelpers.formToObject(form);
            data.copies_count = parseInt(data.copies_count, 10) || 0;

            return LmsApi.addSaleStock(data).then(function (res) {
                LmsHelpers.afterFormSave(res, {
                    isEdit: false,
                    indexUrl: indexUrl,
                });
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#saleCopiesForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('saleCopiesTableBody')) {
            loadSaleCopiesList(1);
            LmsHelpers.bindTableFilters(saleCopiesFilterConfig, loadSaleCopiesList);

            $(document).on('click', '.btn-set-sale-stock', function () {
                promptSetSaleStock($(this).data('isbn'), $(this).data('stock'), this);
            });
        }

        initSaleCopiesForm();
    });
})();
