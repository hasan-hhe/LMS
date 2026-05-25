(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.authorsIndex || '/admin/authors';
    const editBaseUrl = indexUrl.replace(/\/$/, '');

    const authorsFilterConfig = {
        search: '#searchAuthors',
    };

    function getAuthorsListParams(page) {
        return LmsHelpers.buildListParams(page, authorsFilterConfig);
    }

    function loadAuthorsList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getAuthors,
            getParams: getAuthorsListParams,
            params: getAuthorsListParams(page || 1),
            tableBodySelector: '#authorsTableBody',
            paginationSelector: '#authorsPagination',
            totalSelector: '#totalAuthors',
            renderRow: function (author, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (author.firstname || '-') + '</td>' +
                    '<td>' + (author.lastname || '-') + '</td>' +
                    '<td>' + (author.full_name || '-') + '</td>' +
                    '<td>' + (author.nationality || '-') + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + author.id + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-author" data-id="' + author.id + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function loadAuthorForm(id) {
        const request = id ? LmsApi.getAuthor(id) : Promise.resolve(null);
        request.then(function (res) {
            const author = res?.data;
            if (!author) return;

            const form = document.getElementById('authorForm');
            if (!form) return;

            form.firstname.value = author.firstname || '';
            form.lastname.value = author.lastname || '';
            form.nationality.value = author.nationality || '';
        }).catch(LmsHelpers.handleApiError);
    }

    function initAuthorForm() {
        const form = document.getElementById('authorForm');
        if (!form) return;

        loadAuthorForm(window.LMS_ENTITY_ID);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            LmsHelpers.clearFormErrors('#authorForm');
            const data = LmsHelpers.formToObject(form);
            const id = window.LMS_ENTITY_ID;
            const request = id ? LmsApi.updateAuthor(id, data) : LmsApi.createAuthor(data);

            request.then(function (res) {
                LmsHelpers.notify('success', res.message);
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#authorForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('authorsTableBody')) {
            loadAuthorsList(1);
            LmsHelpers.bindTableFilters(authorsFilterConfig, loadAuthorsList);

            $(document).on('click', '.btn-delete-author', function () {
                const id = $(this).data('id');
                confirmDelete('هل أنت متأكد من حذف هذا المؤلف؟', function () {
                    LmsApi.deleteAuthor(id).then(function (res) {
                        LmsHelpers.notify('success', res.message);
                        loadAuthorsList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initAuthorForm();
    });
})();
