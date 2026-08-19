(function () {
    'use strict';

    const TOKEN_KEY = 'lms_token';
    const USER_KEY = 'lms_user';

    const apiClient = axios.create({
        baseURL: '/api/v1',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    apiClient.interceptors.request.use(function (config) {
        const token = localStorage.getItem(TOKEN_KEY);
        if (token) {
            config.headers.Authorization = 'Bearer ' + token;
        }
        return config;
    });

    apiClient.interceptors.response.use(
        function (response) {
            return response;
        },
        function (error) {
            const status = error.response?.status;
            const data = error.response?.data;

            if (status === 401) {
                localStorage.removeItem(TOKEN_KEY);
                localStorage.removeItem(USER_KEY);
                if (!window.location.pathname.includes('/admin/login')) {
                    window.location.href = window.LMS_ROUTES?.login || '/admin/login';
                }
            }

            if (status === 403) {
                LmsHelpers.notify('error', LmsHelpers.responseMessage(data, 'ليس لديك صلاحية'));
            }

            return Promise.reject(error);
        }
    );

    function unwrap(response) {
        return response.data;
    }

    window.LmsApi = {
        client: apiClient,
        TOKEN_KEY,
        USER_KEY,

        login(email, password) {
            const url = window.LMS_ROUTES?.apiLogin;
            if (url) {
                return axios.post(url, { email, password }, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then(unwrap);
            }
            return apiClient.post('/admin/auth/login', { email, password }).then(unwrap);
        },

        logout() {
            return apiClient.post('/admin/auth/logout').then(unwrap);
        },

        me() {
            return apiClient.post('/admin/auth/me').then(unwrap);
        },

        getBooks(params) {
            return apiClient.get('/books', { params }).then(unwrap);
        },

        getBook(isbn) {
            return apiClient.get('/books/' + encodeURIComponent(isbn)).then(unwrap);
        },

        createBook(formData) {
            return apiClient.post('/books', formData).then(unwrap);
        },

        updateBook(isbn, formData) {
            return apiClient.put('/books/' + encodeURIComponent(isbn), formData).then(unwrap);
        },

        deleteBook(isbn) {
            return apiClient.delete('/books/' + encodeURIComponent(isbn)).then(unwrap);
        },

        getBookInstances(params) {
            return apiClient.get('/book-instances', { params }).then(unwrap);
        },

        getBookInstance(id) {
            return apiClient.get('/book-instances/' + id).then(unwrap);
        },

        createBookInstance(data) {
            return apiClient.post('/book-instances', data).then(unwrap);
        },

        updateBookInstance(id, data) {
            return apiClient.put('/book-instances/' + id, data).then(unwrap);
        },

        deleteBookInstance(id) {
            return apiClient.delete('/book-instances/' + id).then(unwrap);
        },

        getAuthors(params) {
            return apiClient.get('/authors', { params }).then(unwrap);
        },

        getAuthor(id) {
            return apiClient.get('/authors/' + id).then(unwrap);
        },

        createAuthor(data) {
            return apiClient.post('/authors', data).then(unwrap);
        },

        updateAuthor(id, data) {
            return apiClient.put('/authors/' + id, data).then(unwrap);
        },

        deleteAuthor(id) {
            return apiClient.delete('/authors/' + id).then(unwrap);
        },

        getCategories(params) {
            return apiClient.get('/categories', { params }).then(unwrap);
        },

        getCategory(id) {
            return apiClient.get('/categories/' + id).then(unwrap);
        },

        createCategory(data) {
            return apiClient.post('/categories', data).then(unwrap);
        },

        updateCategory(id, data) {
            return apiClient.put('/categories/' + id, data).then(unwrap);
        },

        deleteCategory(id) {
            return apiClient.delete('/categories/' + id).then(unwrap);
        },

        getPublishers(params) {
            return apiClient.get('/publishers', { params }).then(unwrap);
        },

        getPublisher(id) {
            return apiClient.get('/publishers/' + id).then(unwrap);
        },

        createPublisher(data) {
            return apiClient.post('/publishers', data).then(unwrap);
        },

        updatePublisher(id, data) {
            return apiClient.put('/publishers/' + id, data).then(unwrap);
        },

        deletePublisher(id) {
            return apiClient.delete('/publishers/' + id).then(unwrap);
        },

        getMembers(params) {
            return apiClient.get('/members', { params }).then(unwrap);
        },

        getMember(id) {
            return apiClient.get('/members/' + id).then(unwrap);
        },

        createMember(formData) {
            return apiClient.post('/members', formData).then(unwrap);
        },

        updateMember(id, formData) {
            return apiClient.put('/members/' + id, formData).then(unwrap);
        },

        deleteMember(id) {
            return apiClient.delete('/members/' + id).then(unwrap);
        },

        getMemberBorrowings(id, params) {
            return apiClient.get('/members/' + id + '/borrowings', { params }).then(unwrap);
        },

        getMemberFines(id, params) {
            return apiClient.get('/members/' + id + '/fines', { params }).then(unwrap);
        },

        getLibrarians(params) {
            return apiClient.get('/librarians', { params }).then(unwrap);
        },

        getLibrarian(id) {
            return apiClient.get('/librarians/' + id).then(unwrap);
        },

        createLibrarian(formData) {
            return apiClient.post('/librarians', formData).then(unwrap);
        },

        updateLibrarian(id, formData) {
            return apiClient.put('/librarians/' + id, formData).then(unwrap);
        },

        deleteLibrarian(id) {
            return apiClient.delete('/librarians/' + id).then(unwrap);
        },

        getBorrowings(params) {
            return apiClient.get('/borrowings', { params }).then(unwrap);
        },

        getBorrowing(id) {
            return apiClient.get('/borrowings/' + id).then(unwrap);
        },

        createBorrowing(data) {
            return apiClient.post('/borrowings', data).then(unwrap);
        },

        returnBorrowing(id) {
            return apiClient.put('/borrowings/' + id + '/return').then(unwrap);
        },

        extendBorrowing(id, data) {
            return apiClient.put('/borrowings/' + id + '/extend', data).then(unwrap);
        },

        getFines(params) {
            return apiClient.get('/fines', { params }).then(unwrap);
        },

        payFine(id) {
            return apiClient.put('/fines/' + id + '/pay').then(unwrap);
        },

        getPointsBalance(memberId) {
            return apiClient.get('/points/balance', { params: { member_id: memberId } }).then(unwrap);
        },

        getPointsHistory(memberId, params) {
            return apiClient.get('/points/history', {
                params: Object.assign({}, params || {}, { member_id: memberId }),
            }).then(unwrap);
        },

        topUpPoints(data) {
            return apiClient.post('/points/top-up', data).then(unwrap);
        },

        getPointsSettings() {
            return apiClient.get('/points/settings').then(unwrap);
        },

        updatePointsSettings(data) {
            return apiClient.put('/points/settings', data).then(unwrap);
        },

        adjustPoints(data) {
            return apiClient.post('/points/adjust', data).then(unwrap);
        },

        getTopUpCodes(params) {
            return apiClient.get('/top-up-codes', { params }).then(unwrap);
        },

        generateTopUpCodes(data) {
            return apiClient.post('/top-up-codes/generate', data).then(unwrap);
        },

        getReservations(params) {
            return apiClient.get('/reservations', { params }).then(unwrap);
        },

        createReservation(data) {
            return apiClient.post('/reservations', data).then(unwrap);
        },

        cancelReservation(id) {
            return apiClient.put('/reservations/' + id + '/cancel').then(unwrap);
        },

        markReservationReady(id) {
            return apiClient.put('/reservations/' + id + '/ready').then(unwrap);
        },

        fulfillReservation(id) {
            return apiClient.put('/reservations/' + id + '/fulfill').then(unwrap);
        },

        getOrders(params) {
            return apiClient.get('/orders', { params }).then(unwrap);
        },

        getOrder(id) {
            return apiClient.get('/orders/' + id).then(unwrap);
        },

        createOrder(data) {
            return apiClient.post('/orders', data).then(unwrap);
        },

        updateOrderState(id, data) {
            return apiClient.put('/orders/' + id + '/state', data).then(unwrap);
        },

        getReportStats() {
            return apiClient.get('/reports/stats').then(unwrap);
        },

        getReportOverdue() {
            return apiClient.get('/reports/overdue').then(unwrap);
        },

        getReportMostBorrowed() {
            return apiClient.get('/reports/most-borrowed').then(unwrap);
        },

        getReportFinesSummary() {
            return apiClient.get('/reports/fines-summary').then(unwrap);
        },

        getReportInventory() {
            return apiClient.get('/reports/inventory').then(unwrap);
        },

        getReportPointsSummary() {
            return apiClient.get('/reports/points-summary').then(unwrap);
        },

        downloadReport(path, filename, params) {
            return apiClient.get(path, { params, responseType: 'blob' }).then(function (response) {
                const url = URL.createObjectURL(response.data);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            });
        },

        downloadPointsExport(params) {
            return this.downloadReport('/reports/points-export', 'point-transactions.csv', params);
        },

        downloadFinesExport(params) {
            return this.downloadReport('/reports/fines-export', 'fines.csv', params);
        },

        downloadOverdueExport(params) {
            return this.downloadReport('/reports/overdue-export', 'overdue-borrowings.csv', params);
        },

        getReviews(params) {
            return apiClient.get('/reviews', { params }).then(unwrap);
        },

        getBookReviews(isbn) {
            return apiClient.get('/books/' + encodeURIComponent(isbn) + '/reviews').then(unwrap);
        },

        deleteReview(id) {
            return apiClient.delete('/reviews/' + id).then(unwrap);
        },

        getDigitalAsset(isbn) {
            return apiClient.get('/books/' + encodeURIComponent(isbn) + '/digital').then(unwrap);
        },

        upsertDigitalAsset(isbn, formData) {
            return apiClient.post('/books/' + encodeURIComponent(isbn) + '/digital', formData).then(unwrap);
        },

        deleteDigitalAsset(isbn) {
            return apiClient.delete('/books/' + encodeURIComponent(isbn) + '/digital').then(unwrap);
        },

        getMemberFavorites(memberId, params) {
            return apiClient.get('/favorites', {
                params: Object.assign({}, params || {}, { member_id: memberId }),
            }).then(unwrap);
        },
    };
})();
