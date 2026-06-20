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
                form.amount.value = book.amount || '';
                form.year_of_publishing.value = book.year_of_publishing || '';
                form.number_edition.value = book.number_edition || '';
                if (book.cover_url) {
                    $('#coverPreview').attr('src', book.cover_url).show();
                }
            }
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
                '<p><strong>السعر:</strong> ' + (book.price || 0) + '</p>' +
                '<p><strong>الكمية:</strong> ' + (book.amount || 0) + '</p>' +
                '<p><strong>الوصف:</strong> ' + (book.description || '') + '</p>' +
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
        initBookShow();
    });
})();
