(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.publishersIndex || '/admin/publishers';
    const editBaseUrl = indexUrl.replace(/\/$/, '');

    const publishersFilterConfig = {
        search: '#searchPublishers',
    };

    function getPublishersListParams(page) {
        return LmsHelpers.buildListParams(page, publishersFilterConfig);
    }

    function loadPublishersList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getPublishers,
            getParams: getPublishersListParams,
            params: getPublishersListParams(page || 1),
            tableBodySelector: '#publishersTableBody',
            paginationSelector: '#publishersPagination',
            totalSelector: '#totalPublishers',
            renderRow: function (publisher, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (publisher.name || '-') + '</td>' +
                    '<td>' + (publisher.location || '-') + '</td>' +
                    '<td>' + (publisher.books_count ?? '-') + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + publisher.id + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-publisher" data-id="' + publisher.id + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function loadPublisherForm(id) {
        const request = id ? LmsApi.getPublisher(id) : Promise.resolve(null);
        request.then(function (res) {
            const publisher = res?.data;
            if (!publisher) return;

            const form = document.getElementById('publisherForm');
            if (!form) return;

            form.name.value = publisher.name || '';
            form.location.value = publisher.location || '';
        }).catch(LmsHelpers.handleApiError);
    }

    function initPublisherForm() {
        const form = document.getElementById('publisherForm');
        if (!form) return;

        loadPublisherForm(window.LMS_ENTITY_ID);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            LmsHelpers.clearFormErrors('#publisherForm');
            const data = LmsHelpers.formToObject(form);
            const id = window.LMS_ENTITY_ID;
            const request = id ? LmsApi.updatePublisher(id, data) : LmsApi.createPublisher(data);

            request.then(function (res) {
                LmsHelpers.notify('success', res.message);
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#publisherForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('publishersTableBody')) {
            loadPublishersList(1);
            LmsHelpers.bindTableFilters(publishersFilterConfig, loadPublishersList);

            $(document).on('click', '.btn-delete-publisher', function () {
                const id = $(this).data('id');
                confirmDelete('هل أنت متأكد من حذف دار النشر هذه؟', function () {
                    LmsApi.deletePublisher(id).then(function (res) {
                        LmsHelpers.notify('success', res.message);
                        loadPublishersList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initPublisherForm();
    });
})();
