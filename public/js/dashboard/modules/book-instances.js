(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.bookInstancesIndex || '/admin/book-instances';
    const editBaseUrl = indexUrl.replace(/\/$/, '');

    const bookInstancesFilterConfig = {
        search: '#searchBookInstances',
        fields: {
            state_id: '#filterStateId',
        },
    };

    function itemsFrom(res) {
        return LmsHelpers.extractItems(res);
    }

    function getBookInstancesListParams(page) {
        return LmsHelpers.buildListParams(page, bookInstancesFilterConfig);
    }

    function fillInstanceStateSelect(selector, selectedValue) {
        const states = window.LMS_LOOKUPS?.instanceStates || [];
        LmsHelpers.fillSelect(selector, states, 'id', function (item) {
            return LmsHelpers.instanceStateLabel(item.state);
        }, selectedValue);
        LmsHelpers.initChoices(selector);
    }

    function loadFilters() {
        fillInstanceStateSelect('#filterStateId');
        return Promise.resolve();
    }

    function loadBookInstancesList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getBookInstanceGroups,
            getParams: getBookInstancesListParams,
            params: getBookInstancesListParams(page || 1),
            tableBodySelector: '#bookInstancesTableBody',
            paginationSelector: '#bookInstancesPagination',
            totalSelector: '#totalBookInstances',
            renderRow: function (group, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr class="book-instance-group" data-isbn="' + (group.isbn || '') + '" data-title="' + (group.title || '').replace(/"/g, '&quot;') + '" style="cursor:pointer;">' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (group.title || '-') + '</td>' +
                    '<td><span class="badge bg-primary">' + (group.copies_count ?? 0) + '</span></td>' +
                    '<td>' + (group.available_count ?? 0) + '</td>' +
                    '<td>' +
                    '<button type="button" class="btn btn-sm btn-info btn-view-copies" data-isbn="' + group.isbn + '" data-title="' + (group.title || '').replace(/"/g, '&quot;') + '"><i class="fa fa-eye"></i> عرض النسخ</button>' +
                    '</td></tr>';
            },
        });
    }

    function loadCopiesModal(isbn, title) {
        $('#copiesModalTitle').text('نسخ الاستعارة: ' + (title || isbn));
        $('#copiesModalBody').html('<tr><td colspan="6" class="page-loading"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</td></tr>');
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('bookCopiesModal'));
        modal.show();

        LmsApi.getBookInstances({ book_isbn: isbn, per_page: 100 }).then(function (res) {
            const items = itemsFrom(res);
            if (!items.length) {
                LmsHelpers.showEmpty('#copiesModalBody', 'لا توجد نسخ');
                return;
            }
            let html = '';
            items.forEach(function (instance, index) {
                const stateName = instance.state?.state;
                const restoreBtn = (stateName === 'damaged' || stateName === 'lost')
                    ? ' <button type="button" class="btn btn-sm btn-success btn-restore-book-instance" data-id="' + instance.id + '" data-isbn="' + isbn + '"><i class="fa fa-undo"></i> إعادة للتداول</button>'
                    : '';
                html += '<tr>' +
                    '<td>' + (index + 1) + '</td>' +
                    '<td>' + (instance.id || '-') + '</td>' +
                    '<td>' + LmsHelpers.instanceStateLabel(instance.state?.state) + '</td>' +
                    '<td>' + LmsHelpers.conditionLabel(instance.condition) + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + instance.id + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-book-instance" data-id="' + instance.id + '" data-isbn="' + isbn + '" data-title="' + (title || '').replace(/"/g, '&quot;') + '"><i class="fa fa-trash"></i></button>' +
                    restoreBtn +
                    '</td></tr>';
            });
            $('#copiesModalBody').html(html);
        }).catch(function (error) {
            LmsHelpers.handleApiError(error);
            LmsHelpers.showEmpty('#copiesModalBody', 'تعذر تحميل النسخ');
        });
    }

    function loadBookInstanceForm(id) {
        const isEdit = !!id;

        Promise.all([
            isEdit ? LmsApi.getBookInstance(id) : Promise.resolve(null),
        ]).then(function (results) {
            const instance = results[0]?.data;
            fillInstanceStateSelect('#state_id', instance?.state?.id);
            LmsHelpers.initChoices('#condition');

            if (isEdit && instance) {
                const bookLabel = (instance.book?.title || '') +
                    (instance.book?.isbn ? ' (' + instance.book.isbn + ')' : '');
                $('#bookIsbnDisplay').val(bookLabel || instance.book?.isbn || '-');
                if (instance.condition) {
                    const conditionEl = document.querySelector('#condition');
                    if (conditionEl && conditionEl.choicesInstance) {
                        conditionEl.choicesInstance.setChoiceByValue(instance.condition);
                    } else {
                        $('#condition').val(instance.condition);
                    }
                }
            } else {
                LmsHelpers.initRemoteSelect('#book_ISBN', {
                    placeholder: 'اختر الكتاب',
                    valueKey: 'isbn',
                    labelFn: function (book) {
                        return (book.title || '') + ' (' + (book.isbn || '') + ')';
                    },
                    fetchFn: function (query) {
                        return LmsApi.getBooks({ search: query, per_page: 30 }).then(itemsFrom);
                    },
                });
            }
        }).catch(LmsHelpers.handleApiError);
    }

    function initBookInstanceForm() {
        const form = document.getElementById('bookInstanceForm');
        if (!form) return;

        loadBookInstanceForm(window.LMS_ENTITY_ID);

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#bookInstanceForm');
            const id = window.LMS_ENTITY_ID;
            const data = LmsHelpers.formToObject(form);
            if (data.copies_count) {
                data.copies_count = parseInt(data.copies_count, 10) || 1;
            }
            const request = id
                ? LmsApi.updateBookInstance(id, { state_id: data.state_id, condition: data.condition })
                : LmsApi.createBookInstance(data);

            return request.then(function (res) {
                LmsHelpers.afterFormSave(res, {
                    isEdit: !!id,
                    indexUrl: indexUrl,
                });
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#bookInstanceForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('bookInstancesTableBody')) {
            loadFilters().then(function () {
                loadBookInstancesList(1);
                LmsHelpers.bindTableFilters(bookInstancesFilterConfig, loadBookInstancesList);
            });

            $(document).on('click', '.btn-view-copies, tr.book-instance-group', function (e) {
                if ($(e.target).closest('.btn-delete-book-instance').length) return;
                const $row = $(this).closest('tr');
                const isbn = $(this).data('isbn') || $row.data('isbn');
                const title = $(this).data('title') || $row.data('title');
                if (isbn) {
                    loadCopiesModal(isbn, title);
                }
            });

            $(document).on('click', '.btn-delete-book-instance', function (e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const isbn = $(this).data('isbn');
                const title = $(this).data('title');
                confirmDelete('هل أنت متأكد من حذف هذه النسخة؟', function () {
                    LmsApi.deleteBookInstance(id).then(function (res) {
                        LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                        loadBookInstancesList(1);
                        if (isbn) {
                            loadCopiesModal(isbn, title);
                        }
                    }).catch(LmsHelpers.handleApiError);
                });
            });

            $(document).on('click', '.btn-restore-book-instance', function (e) {
                e.stopPropagation();
                const btn = this;
                const id = $(this).data('id');
                const isbn = $(this).data('isbn');
                LmsHelpers.withBusy(btn, function () {
                    return LmsApi.restoreBookInstance(id).then(function (res) {
                        LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                        loadBookInstancesList(1);
                        if (isbn) {
                            loadCopiesModal(isbn);
                        }
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initBookInstanceForm();
    });
})();
