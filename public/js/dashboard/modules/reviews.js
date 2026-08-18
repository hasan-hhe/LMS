(function () {
    'use strict';

    const reviewsFilterConfig = {
        search: '#searchReviews',
        fields: {
            isbn: '#filterIsbn',
        },
    };

    function getReviewsListParams(page) {
        return LmsHelpers.buildListParams(page, reviewsFilterConfig);
    }

    function loadReviewsList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getReviews,
            getParams: getReviewsListParams,
            params: getReviewsListParams(page || 1),
            tableBodySelector: '#reviewsTableBody',
            paginationSelector: '#reviewsPagination',
            totalSelector: '#totalReviews',
            renderRow: function (review, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (review.book?.title || review.book?.isbn || '-') + '</td>' +
                    '<td>' + (review.user?.full_name || '-') + '</td>' +
                    '<td>' + (review.rate ?? '-') + '</td>' +
                    '<td>' + (review.comment || '-') + '</td>' +
                    '<td>' +
                    '<button type="button" class="btn btn-sm btn-danger btn-delete-review" data-id="' + review.id + '">' +
                    '<i class="fa fa-trash"></i></button>' +
                    '</td></tr>';
            },
        });
    }

    runWhenDashboardReady(function () {
        if (!document.getElementById('reviewsTableBody')) return;

        loadReviewsList(1);
        LmsHelpers.bindTableFilters(reviewsFilterConfig, loadReviewsList);

        $(document).on('click', '.btn-delete-review', function () {
            const id = $(this).data('id');
            confirmDelete('هل أنت متأكد من حذف هذا التقييم؟', function () {
                LmsApi.deleteReview(id).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                    loadReviewsList(1);
                }).catch(LmsHelpers.handleApiError);
            });
        });
    });
})();
