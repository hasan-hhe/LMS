(function () {
    'use strict';

    const indexUrl = window.LMS_ROUTES?.digitalAssetsIndex || '/admin/digital-assets';
    const editBaseUrl = indexUrl.replace(/\/$/, '');

    const filterConfig = {
        search: '#searchDigitalAssets',
    };

    function itemsFrom(res) {
        return LmsHelpers.extractItems(res);
    }

    function getListParams(page) {
        return LmsHelpers.buildListParams(page, filterConfig);
    }

    function loadDigitalAssetsList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getDigitalAssets,
            getParams: getListParams,
            params: getListParams(page || 1),
            tableBodySelector: '#digitalAssetsTableBody',
            paginationSelector: '#digitalAssetsPagination',
            totalSelector: '#totalDigitalAssets',
            renderRow: function (asset, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const isbn = asset.isbn || asset.book?.isbn || '';
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (asset.book?.title || '-') + '</td>' +
                    '<td>' + (isbn || '-') + '</td>' +
                    '<td>' + (asset.has_pdf ? 'نعم' : 'لا') + '</td>' +
                    '<td>' + (asset.has_audio ? 'نعم' : 'لا') + '</td>' +
                    '<td>' + (asset.is_free ? 'مجاني' : 'مدفوع') + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + encodeURIComponent(isbn) + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-digital" data-isbn="' + isbn + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function applyDigitalFiles(asset) {
        LmsHelpers.setFileCurrentUrl('#digital_pdf', asset?.pdf_url || '');
        LmsHelpers.setFileCurrentUrl('#digital_audio', asset?.audio_url || '');
        LmsHelpers.enhanceFileInputs(document.getElementById('digitalAssetPageForm') || document);
    }

    function fillForm(asset) {
        const form = document.getElementById('digitalAssetPageForm');
        if (!form || !asset) return;
        if (form.querySelector('#digital_is_free')) {
            form.querySelector('#digital_is_free').checked = !!asset.is_free;
        }
        applyDigitalFiles(asset);
    }

    function initDigitalAssetForm() {
        const form = document.getElementById('digitalAssetPageForm');
        if (!form) return;

        const isbn = window.LMS_DIGITAL_ISBN;
        if (isbn) {
            LmsApi.getDigitalAsset(isbn).then(function (res) {
                fillForm(res.data);
            }).catch(LmsHelpers.handleApiError);
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

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#digitalAssetPageForm');
            const formData = LmsHelpers.formToFormData(form);
            formData.append('is_free', document.getElementById('digital_is_free').checked ? '1' : '0');
            if (document.getElementById('digital_remove_pdf')?.checked) {
                formData.append('remove_pdf', '1');
            }
            if (document.getElementById('digital_remove_audio')?.checked) {
                formData.append('remove_audio', '1');
            }

            const request = isbn
                ? LmsApi.upsertDigitalAsset(isbn, formData)
                : LmsApi.createDigitalAsset(formData);

            return request.then(function (res) {
                LmsHelpers.afterFormSave(res, {
                    isEdit: !!isbn,
                    indexUrl: indexUrl,
                });
                if (isbn) {
                    fillForm(res.data);
                }
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#digitalAssetPageForm');
            });
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('digitalAssetsTableBody')) {
            loadDigitalAssetsList(1);
            LmsHelpers.bindTableFilters(filterConfig, loadDigitalAssetsList);

            $(document).on('click', '.btn-delete-digital', function () {
                const isbn = $(this).data('isbn');
                confirmDelete('هل أنت متأكد من حذف هذا المحتوى الرقمي؟', function () {
                    LmsApi.deleteDigitalAsset(isbn).then(function (res) {
                        LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                        loadDigitalAssetsList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initDigitalAssetForm();
    });
})();
