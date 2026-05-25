(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.categoriesIndex || '/admin/categories';
    const editBaseUrl = indexUrl.replace(/\/$/, '');

    const categoriesFilterConfig = {
        search: '#searchCategories',
    };

    function getCategoriesListParams(page) {
        return LmsHelpers.buildListParams(page, categoriesFilterConfig);
    }

    function loadCategoriesList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getCategories,
            getParams: getCategoriesListParams,
            params: getCategoriesListParams(page || 1),
            tableBodySelector: '#categoriesTableBody',
            paginationSelector: '#categoriesPagination',
            totalSelector: '#totalCategories',
            renderRow: function (category, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const description = category.description || '-';
                const shortDesc = description.length > 80 ? description.substring(0, 80) + '...' : description;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (category.title || '-') + '</td>' +
                    '<td>' + shortDesc + '</td>' +
                    '<td>' + (category.books_count ?? '-') + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + category.id + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-category" data-id="' + category.id + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function loadCategoryForm(id) {
        const request = id ? LmsApi.getCategory(id) : Promise.resolve(null);
        request.then(function (res) {
            const category = res?.data;
            if (!category) return;

            const form = document.getElementById('categoryForm');
            if (!form) return;

            form.title.value = category.title || '';
            form.discription.value = category.description || '';
        }).catch(LmsHelpers.handleApiError);
    }

    function initCategoryForm() {
        const form = document.getElementById('categoryForm');
        if (!form) return;

        loadCategoryForm(window.LMS_ENTITY_ID);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            LmsHelpers.clearFormErrors('#categoryForm');
            const data = LmsHelpers.formToObject(form);
            const id = window.LMS_ENTITY_ID;
            const request = id ? LmsApi.updateCategory(id, data) : LmsApi.createCategory(data);

            request.then(function (res) {
                LmsHelpers.notify('success', res.message);
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 500);
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#categoryForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('categoriesTableBody')) {
            loadCategoriesList(1);
            LmsHelpers.bindTableFilters(categoriesFilterConfig, loadCategoriesList);

            $(document).on('click', '.btn-delete-category', function () {
                const id = $(this).data('id');
                confirmDelete('هل أنت متأكد من حذف هذا التصنيف؟', function () {
                    LmsApi.deleteCategory(id).then(function (res) {
                        LmsHelpers.notify('success', res.message);
                        loadCategoriesList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initCategoryForm();
    });
})();
