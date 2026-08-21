(function () {
    'use strict';

    const booksIndexUrl = window.LMS_ROUTES?.booksIndex || '/admin/books';
    const editBaseUrl = booksIndexUrl.replace(/\/$/, '');

    const booksFilterConfig = {
        search: '#searchBooks',
        fields: {
            category_id: '#filterCategory',
            author_id: '#filterAuthor',
        },
    };

    function itemsFrom(res) {
        return LmsHelpers.extractItems(res);
    }

    function getBooksListParams(page) {
        return LmsHelpers.buildListParams(page, booksFilterConfig);
    }

    function loadFilters() {
        LmsHelpers.initRemoteSelect('#filterCategory', {
            placeholder: 'كل التصنيفات',
            valueKey: 'id',
            labelFn: 'title',
            fetchFn: function (query) {
                return LmsApi.getCategories({ search: query, per_page: 30 }).then(itemsFrom);
            },
        });
        LmsHelpers.initRemoteSelect('#filterAuthor', {
            placeholder: 'كل المؤلفين',
            valueKey: 'id',
            labelFn: 'full_name',
            fetchFn: function (query) {
                return LmsApi.getAuthors({ search: query, per_page: 30 }).then(itemsFrom);
            },
        });
        return Promise.resolve();
    }

    function loadBooksList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getBooks,
            getParams: getBooksListParams,
            params: getBooksListParams(page || 1),
            tableBodySelector: '#booksTableBody',
            paginationSelector: '#booksPagination',
            totalSelector: '#totalBooks',
            renderRow: function (book, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (book.isbn || '-') + '</td>' +
                    '<td>' + (book.title || '-') + '</td>' +
                    '<td>' + (book.author?.full_name || '-') + '</td>' +
                    '<td>' + (book.category?.title || '-') + '</td>' +
                    '<td>' + (book.price_points ?? 0) + '</td>' +
                    '<td>' + ((book.borrow_points ?? 0) > 0 ? book.borrow_points : 'مجانية') + '</td>' +
                    '<td>' + (book.sale_stock ?? book.amount ?? 0) + '</td>' +
                    '<td>' + (book.copies_count ?? book.instances_count ?? 0) + '</td>' +
                    '<td>' + (book.year_of_publishing || '-') + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + encodeURIComponent(book.isbn) + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a> ' +
                    '<a href="' + editBaseUrl + '/' + encodeURIComponent(book.isbn) + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-book" data-isbn="' + book.isbn + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function bindLookupSelects(book) {
        LmsHelpers.initRemoteSelect('#auther_id', {
            placeholder: 'اختر المؤلف',
            valueKey: 'id',
            labelFn: 'full_name',
            selectedValue: book?.author?.id,
            fetchFn: function (query) {
                return LmsApi.getAuthors({ search: query, per_page: 30 }).then(function (res) {
                    const items = itemsFrom(res);
                    if (book?.author && !items.some(function (item) { return String(item.id) === String(book.author.id); })) {
                        items.unshift(book.author);
                    }
                    return items;
                });
            },
        });
        LmsHelpers.initRemoteSelect('#catagory_id', {
            placeholder: 'اختر التصنيف',
            valueKey: 'id',
            labelFn: 'title',
            selectedValue: book?.category?.id,
            fetchFn: function (query) {
                return LmsApi.getCategories({ search: query, per_page: 30 }).then(function (res) {
                    const items = itemsFrom(res);
                    if (book?.category && !items.some(function (item) { return String(item.id) === String(book.category.id); })) {
                        items.unshift(book.category);
                    }
                    return items;
                });
            },
        });
        LmsHelpers.initRemoteSelect('#publisher_id', {
            placeholder: 'اختر دار النشر',
            valueKey: 'id',
            labelFn: 'name',
            selectedValue: book?.publisher?.id,
            fetchFn: function (query) {
                return LmsApi.getPublishers({ search: query, per_page: 30 }).then(function (res) {
                    const items = itemsFrom(res);
                    if (book?.publisher && !items.some(function (item) { return String(item.id) === String(book.publisher.id); })) {
                        items.unshift(book.publisher);
                    }
                    return items;
                });
            },
        });
    }

    function applyBookFiles(book) {
        LmsHelpers.setFileCurrentUrl('[name="cover_image"]', book?.cover_url || '');
        LmsHelpers.setFileCurrentUrl('#digital_pdf', book?.digital?.pdf_url || '');
        LmsHelpers.setFileCurrentUrl('#digital_audio', book?.digital?.audio_url || '');
        LmsHelpers.enhanceFileInputs(document.getElementById('bookForm') || document);
    }

    function loadBookForm(isbn) {
        const request = isbn ? LmsApi.getBook(isbn) : Promise.resolve(null);
        request.then(function (res) {
            const book = res?.data;
            bindLookupSelects(book);

            if (!book) return;

            const form = document.getElementById('bookForm');
            form.title.value = book.title || '';
            form.discription.value = book.description || '';
            form.price.value = book.price || '';
            form.price_points.value = book.price_points ?? '';
            applyBorrowPointsFields(book.borrow_points);
            form.amount.value = book.amount ?? '';
            const copiesField = document.getElementById('current_copies_count');
            if (copiesField) {
                copiesField.value = book.copies_count ?? book.instances_count ?? 0;
            }
            form.year_of_publishing.value = book.year_of_publishing || '';
            form.number_edition.value = book.number_edition || '';
            applyBookFiles(book);
            fillDigitalForm(book.digital);
        }).catch(LmsHelpers.handleApiError);
    }

    function applyBorrowPointsFields(borrowPoints) {
        const checkbox = document.getElementById('has_borrow_points');
        const input = document.querySelector('#bookForm [name="borrow_points"]');
        if (!checkbox || !input) return;
        const points = Number(borrowPoints || 0);
        checkbox.checked = points > 0;
        input.value = points > 0 ? points : 0;
        syncBorrowPointsFields();
    }

    function syncBorrowPointsFields() {
        const checkbox = document.getElementById('has_borrow_points');
        const wrap = document.getElementById('borrowPointsWrap');
        const input = document.querySelector('#bookForm [name="borrow_points"]');
        if (!checkbox || !input) return;
        if (checkbox.checked) {
            wrap?.classList.remove('d-none');
            input.disabled = false;
            input.min = '1';
            input.required = true;
            if (!input.value || Number(input.value) < 1) {
                input.value = '';
            }
        } else {
            wrap?.classList.add('d-none');
            input.required = false;
            input.min = '0';
            input.value = '0';
            input.disabled = false;
        }
    }

    function fillDigitalForm(digital) {
        const isFree = document.getElementById('digital_is_free');
        const removePdf = document.getElementById('digital_remove_pdf');
        const removeAudio = document.getElementById('digital_remove_audio');
        if (!isFree) return;
        isFree.checked = !!digital?.is_free;
        if (removePdf) removePdf.checked = false;
        if (removeAudio) removeAudio.checked = false;
        LmsHelpers.setFileCurrentUrl('#digital_pdf', digital?.pdf_url || '');
        LmsHelpers.setFileCurrentUrl('#digital_audio', digital?.audio_url || '');
    }

    function initDigitalAssetForm() {
        const isbn = window.LMS_BOOK_ISBN;
        if (!isbn || !document.getElementById('digitalAssetForm')) return;

        $('#btnSaveDigital').on('click', function () {
            const btn = this;
            const formData = new FormData();
            const pdf = document.getElementById('digital_pdf')?.files?.[0];
            const audio = document.getElementById('digital_audio')?.files?.[0];
            if (pdf) formData.append('pdf', pdf);
            if (audio) formData.append('audio', audio);
            formData.append('is_free', document.getElementById('digital_is_free').checked ? '1' : '0');
            if (document.getElementById('digital_remove_pdf')?.checked) {
                formData.append('remove_pdf', '1');
            }
            if (document.getElementById('digital_remove_audio')?.checked) {
                formData.append('remove_audio', '1');
            }
            LmsHelpers.withBusy(btn, function () {
                return LmsApi.upsertDigitalAsset(isbn, formData).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                    fillDigitalForm(res.data);
                    LmsHelpers.enhanceFileInputs('#digitalAssetForm');
                }).catch(LmsHelpers.handleApiError);
            });
        });

        $('#btnDeleteDigital').on('click', function () {
            confirmDelete('هل أنت متأكد من حذف المحتوى الرقمي؟', function () {
                LmsApi.deleteDigitalAsset(isbn).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                    fillDigitalForm(null);
                }).catch(LmsHelpers.handleApiError);
            });
        });
    }

    function initBookForm() {
        const form = document.getElementById('bookForm');
        if (!form) return;

        loadBookForm(window.LMS_BOOK_ISBN);
        syncBorrowPointsFields();
        $('#has_borrow_points').on('change', syncBorrowPointsFields);

        LmsHelpers.bindBusyForm(form, function () {
            LmsHelpers.clearFormErrors('#bookForm');
            const formData = LmsHelpers.formToFormData(form);
            const isbn = window.LMS_BOOK_ISBN;
            const request = isbn ? LmsApi.updateBook(isbn, formData) : LmsApi.createBook(formData);

            return request.then(function (res) {
                LmsHelpers.afterFormSave(res, {
                    isEdit: !!isbn,
                    indexUrl: booksIndexUrl,
                });
                if (isbn && res.data) {
                    applyBookFiles(res.data);
                }
            }).catch(function (error) {
                LmsHelpers.handleApiError(error, '#bookForm');
            });
        });
    }

    function initBookShow() {
        if (!window.LMS_BOOK_SHOW || !window.LMS_BOOK_ISBN) return;
        LmsApi.getBook(window.LMS_BOOK_ISBN).then(function (res) {
            const book = res.data;
            $('#bookShowContent').html(
                '<div class="row">' +
                '<div class="col-md-3">' + (book.cover_url ? '<img src="' + book.cover_url + '" class="img-fluid rounded">' : '') + '</div>' +
                '<div class="col-md-9">' +
                '<h4>' + (book.title || '') + '</h4>' +
                '<p><strong>ISBN:</strong> ' + (book.isbn || '') + '</p>' +
                '<p><strong>المؤلف:</strong> ' + (book.author?.full_name || '-') + '</p>' +
                '<p><strong>التصنيف:</strong> ' + (book.category?.title || '-') + '</p>' +
                '<p><strong>دار النشر:</strong> ' + (book.publisher?.name || '-') + '</p>' +
                '<p><strong>السعر (ل.س):</strong> ' + (book.price || 0) + '</p>' +
                '<p><strong>سعر النقاط:</strong> ' + (book.price_points ?? 0) + ' نقطة</p>' +
                '<p><strong>نقاط الاستعارة:</strong> ' + ((book.borrow_points ?? 0) > 0 ? book.borrow_points + ' نقطة' : 'مجانية') + '</p>' +
                '<p><strong>نسخ البيع:</strong> ' + (book.sale_stock ?? book.amount ?? 0) + '</p>' +
                '<p><strong>نسخ الاستعارة:</strong> ' + (book.copies_count ?? book.instances_count ?? 0) + '</p>' +
                '<p><strong>الوصف:</strong> ' + (book.description || '') + '</p>' +
                '<hr><h5>المحتوى الرقمي</h5>' +
                (book.digital
                    ? '<p><strong>PDF:</strong> ' + (book.digital.has_pdf
                        ? (book.digital.pdf_url
                            ? '<a href="' + book.digital.pdf_url + '" target="_blank" rel="noopener">تحميل</a>'
                            : 'موجود')
                        : '-') + '</p>' +
                      '<p><strong>صوت:</strong> ' + (book.digital.has_audio
                        ? (book.digital.audio_url
                            ? '<a href="' + book.digital.audio_url + '" target="_blank" rel="noopener">استماع / تحميل</a>'
                            : 'موجود')
                        : '-') + '</p>' +
                      '<p><strong>مجاني:</strong> ' + (book.digital.is_free ? 'نعم' : 'لا') + '</p>'
                    : '<p class="text-muted">لا يوجد محتوى رقمي</p>') +
                '</div></div>'
            );
        }).catch(function (error) {
            LmsHelpers.handleApiError(error);
        });
    }

    runWhenDashboardReady(function () {
        if (document.getElementById('booksTableBody')) {
            loadFilters();
            loadBooksList(1);
            LmsHelpers.bindTableFilters(booksFilterConfig, loadBooksList);

            $(document).on('click', '.btn-delete-book', function () {
                const isbn = $(this).data('isbn');
                confirmDelete('هل أنت متأكد من حذف هذا الكتاب؟', function () {
                    LmsApi.deleteBook(isbn).then(function (res) {
                        LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                        loadBooksList(1);
                    }).catch(LmsHelpers.handleApiError);
                });
            });
        }

        initBookForm();
        initDigitalAssetForm();
        initBookShow();
    });
})();
