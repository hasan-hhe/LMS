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

    function getBooksListParams(page) {
        return LmsHelpers.buildListParams(page, booksFilterConfig);
    }

    function loadFilters() {
        return Promise.all([
            LmsApi.getCategories({ per_page: 100 }),
            LmsApi.getAuthors({ per_page: 100 }),
        ]).then(function (results) {
            LmsHelpers.fillSelect('#filterCategory', results[0].data, 'id', 'title');
            LmsHelpers.fillSelect('#filterAuthor', results[1].data, 'id', 'full_name');
        });
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
                    '<td>' + (book.year_of_publishing || '-') + '</td>' +
                    '<td>' +
                    '<a href="' + editBaseUrl + '/' + encodeURIComponent(book.isbn) + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a> ' +
                    '<a href="' + editBaseUrl + '/' + encodeURIComponent(book.isbn) + '/edit" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a> ' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-book" data-isbn="' + book.isbn + '"><i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    function loadBookForm(isbn) {
        Promise.all([
            LmsApi.getAuthors({ per_page: 200 }),
            LmsApi.getCategories({ per_page: 200 }),
            LmsApi.getPublishers({ per_page: 200 }),
            isbn ? LmsApi.getBook(isbn) : Promise.resolve(null),
        ]).then(function (results) {
            const authors = results[0].data || [];
            const categories = results[1].data || [];
            const publishers = results[2].data || [];
            const book = results[3]?.data;

            LmsHelpers.fillSelect('#auther_id', authors, 'id', 'full_name', book?.author?.id);
            LmsHelpers.fillSelect('#catagory_id', categories, 'id', 'title', book?.category?.id);
            LmsHelpers.fillSelect('#publisher_id', publishers, 'id', 'name', book?.publisher?.id);

            if (book) {
                const form = document.getElementById('bookForm');
                form.title.value = book.title || '';
                form.discription.value = book.description || '';
                form.price.value = book.price || '';
                form.price_points.value = book.price_points ?? '';
                form.amount.value = book.amount || '';
                form.year_of_publishing.value = book.year_of_publishing || '';
                form.number_edition.value = book.number_edition || '';
                if (book.cover_url) {
                    $('#coverPreview').attr('src', book.cover_url).show();
                }
                fillDigitalForm(book.digital);
            }
        });
    }

    function fillDigitalForm(digital) {
        const isFree = document.getElementById('digital_is_free');
        const removePdf = document.getElementById('digital_remove_pdf');
        const removeAudio = document.getElementById('digital_remove_audio');
        const pdfInput = document.getElementById('digital_pdf');
        const audioInput = document.getElementById('digital_audio');
        if (!isFree) return;
        isFree.checked = !!digital?.is_free;
        if (removePdf) removePdf.checked = false;
        if (removeAudio) removeAudio.checked = false;
        if (pdfInput) pdfInput.value = '';
        if (audioInput) audioInput.value = '';
        setDigitalCurrent('digital_pdf_current', digital?.has_pdf, digital?.pdf_url, 'PDF', digital?.pdf_size);
        setDigitalCurrent('digital_audio_current', digital?.has_audio, digital?.audio_url, 'صوتي', digital?.audio_size);
    }

    function setDigitalCurrent(elementId, hasFile, url, label, size) {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (!hasFile) {
            el.textContent = 'لا يوجد ملف ' + label;
            return;
        }
        const sizeText = size ? ' (' + formatDigitalSize(size) + ')' : '';
        if (url) {
            el.innerHTML = 'ملف ' + label + ' مرفوع' + sizeText + ' — <a href="' + url + '" target="_blank" rel="noopener">فتح / تحميل</a>';
            return;
        }
        el.textContent = 'ملف ' + label + ' مرفوع' + sizeText;
    }

    function formatDigitalSize(bytes) {
        if (!bytes || bytes < 1024) return (bytes || 0) + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function initDigitalAssetForm() {
        const isbn = window.LMS_BOOK_ISBN;
        if (!isbn || !document.getElementById('digitalAssetForm')) return;

        $('#btnSaveDigital').on('click', function () {
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
            LmsApi.upsertDigitalAsset(isbn, formData).then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                fillDigitalForm(res.data);
            }).catch(LmsHelpers.handleApiError);
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

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            LmsHelpers.clearFormErrors('#bookForm');
            const formData = LmsHelpers.formToFormData(form);
            const isbn = window.LMS_BOOK_ISBN;
            const request = isbn ? LmsApi.updateBook(isbn, formData) : LmsApi.createBook(formData);

            request.then(function (res) {
                LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                setTimeout(function () {
                    window.location.href = booksIndexUrl;
                }, 500);
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
                '<p><strong>الكمية:</strong> ' + (book.amount || 0) + '</p>' +
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
            loadFilters().then(function () {
                loadBooksList(1);
                LmsHelpers.bindTableFilters(booksFilterConfig, loadBooksList);
            });

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
