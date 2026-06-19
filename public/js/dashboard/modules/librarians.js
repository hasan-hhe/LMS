(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.librariansIndex || '/admin/librarians';
    const editBaseUrl = indexUrl.replace(/\/$/, '');

    const librariansFilterConfig = {
        search: '#searchLibrarians',
    };

    function getLibrariansListParams(page) {
        return LmsHelpers.buildListParams(page, librariansFilterConfig);
    }

    function loadLibrariansList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getLibrarians,
            getParams: getLibrariansListParams,
            params: getLibrariansListParams(page || 1),
            tableBodySelector: '#librariansTableBody',
            paginationSelector: '#librariansPagination',
            totalSelector: '#totalLibrarians',
            renderRow: function (librarian, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (librarian.full_name || '-') + '</td>' +
                    '<td>' + (librarian.email || '-') + '</td>' +
                    '<td>' + (librarian.phone || '-') + '</td>' +
                    '<td>' + (librarian.identity_number || '-') + '</td>' +
                    '<td>' + LmsHelpers.memberStateBadge(librarian.state) + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + librarian.id + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-librarian" data-id="' + librarian.id + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function loadLibrarianForm(librarianId) {
        if (!librarianId) return;

        LmsApi.getLibrarian(librarianId).then(function (res) {
            const librarian = res.data;
            const form = document.getElementById('librarianForm');
            if (!form || !librarian) return;

            form.first_name.value = librarian.first_name || '';
            form.last_name.value = librarian.last_name || '';
            form.email.value = librarian.email || '';
            form.phone.value = librarian.phone || '';
            form.adress.value = librarian.adress || '';
            if (form.state) {
                form.state.value = librarian.state || 'ACTIVE';
            }

            if (librarian.photo_url) {
                $('#photoPreview').attr('src', librarian.photo_url).show();
            }
        }).catch(LmsHelpers.handleApiError);
    }

    function initLibrarianForm() {
        const form = document.getElementById('librarianForm');
        if (!form) return;

        loadLibrarianForm(window.LMS_LIBRARIAN_ID);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            LmsHelpers.clearFormErrors('#librarianForm');
            const formData = LmsHelpers.formToFormData(form);
            const librarianId = window.LMS_LIBRARIAN_ID;
            const request = librarianId
                ? LmsApi.updateLibrarian(librarianId, formData)
                : LmsApi.createLibrarian(formData);

            request.then(function (res) {
                LmsHelpers.notify('success', res.message);
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#librarianForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('librariansTableBody')) {
            loadLibrariansList(1);
            LmsHelpers.bindTableFilters(librariansFilterConfig, loadLibrariansList);

            $(document).on('click', '.btn-delete-librarian', function () {
                const id = $(this).data('id');
                confirmDelete('هل أنت متأكد من حذف أمين المكتبة؟', function () {
                    LmsApi.deleteLibrarian(id).then(function (res) {
                        LmsHelpers.notify('success', res.message);
                        loadLibrariansList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initLibrarianForm();
    });
})();
