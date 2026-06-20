(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.bookInstancesIndex || '/admin/book-instances';
    const editBaseUrl = indexUrl.replace(/\/$/, '');

    const bookInstancesFilterConfig = {
        fields: {
            book_isbn: '#filterBookIsbn',
            state_id: '#filterStateId',
        },
    };

    function getBookInstancesListParams(page) {
        return LmsHelpers.buildListParams(page, bookInstancesFilterConfig);
    }

    function fillInstanceStateSelect(selector, selectedValue) {
        const states = window.LMS_LOOKUPS?.instanceStates || [];
        LmsHelpers.fillSelect(selector, states, 'id', function (item) {
            return LmsHelpers.instanceStateLabel(item.state);
        }, selectedValue);
    }

    function fillBookSelect(selector, selectedIsbn) {
        return LmsApi.getBooks({ per_page: 200 }).then(function (res) {
            const books = res.data || [];
            LmsHelpers.fillSelect(selector, books, 'isbn', function (book) {
                return (book.title || '') + ' (' + (book.isbn || '') + ')';
            }, selectedIsbn);
            return books;
        });
    }

    function loadFilters() {
        fillInstanceStateSelect('#filterStateId');
        return fillBookSelect('#filterBookIsbn');
    }

    function loadBookInstancesList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getBookInstances,
            getParams: getBookInstancesListParams,
            params: getBookInstancesListParams(page || 1),
            tableBodySelector: '#bookInstancesTableBody',
            paginationSelector: '#bookInstancesPagination',
            totalSelector: '#totalBookInstances',
            renderRow: function (instance, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (instance.book?.isbn || '-') + '</td>' +
                    '<td>' + (instance.book?.title || '-') + '</td>' +
                    '<td>' + LmsHelpers.instanceStateLabel(instance.state?.state) + '</td>' +
                    '<td>' + LmsHelpers.conditionLabel(instance.condition) + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + instance.id + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-book-instance" data-id="' + instance.id + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function loadBookInstanceForm(id) {
        const isEdit = !!id;

        Promise.all([
            isEdit ? LmsApi.getBookInstance(id) : Promise.resolve(null),
            isEdit ? Promise.resolve(null) : fillBookSelect('#book_ISBN'),
        ]).then(function (results) {
            const instance = results[0]?.data;
            fillInstanceStateSelect('#state_id', instance?.state?.id);

            if (isEdit && instance) {
                const bookLabel = (instance.book?.title || '') +
                    (instance.book?.isbn ? ' (' + instance.book.isbn + ')' : '');
                $('#bookIsbnDisplay').val(bookLabel || instance.book?.isbn || '-');
                $('#condition').val(instance.condition || '');
            } else {
                fillInstanceStateSelect('#state_id');
            }
        }).catch(LmsHelpers.handleApiError);
    }

    function initBookInstanceForm() {
        const form = document.getElementById('bookInstanceForm');
        if (!form) return;

        loadBookInstanceForm(window.LMS_ENTITY_ID);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            LmsHelpers.clearFormErrors('#bookInstanceForm');
            const id = window.LMS_ENTITY_ID;
            const data = LmsHelpers.formToObject(form);
            const request = id
                ? LmsApi.updateBookInstance(id, { state_id: data.state_id, condition: data.condition })
                : LmsApi.createBookInstance(data);

            request.then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 500);
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

            $(document).on('click', '.btn-delete-book-instance', function () {
                const id = $(this).data('id');
                confirmDelete('هل أنت متأكد من حذف هذه النسخة؟', function () {
                    LmsApi.deleteBookInstance(id).then(function (res) {
                        LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                        loadBookInstancesList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initBookInstanceForm();
    });
})();
