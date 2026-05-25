(function () {
    'use strict';

    runWhenDashboardReady(function () {
        if (!document.getElementById('statsCards')) return;

        LmsApi.getReportStats().then(function (res) {
            const stats = res.data || {};
            $('#statTotalBooks').text(stats.total_books ?? 0);
            $('#statTotalMembers').text(stats.total_members ?? 0);
            $('#statActiveBorrowings').text(stats.active_borrowings ?? 0);
            $('#statOverdueBorrowings').text(stats.overdue_borrowings ?? 0);
            $('#statFinesUnpaid').text(stats.total_fines_unpaid ?? 0);
            $('#statFinesCollected').text(stats.total_fines_collected ?? 0);
            $('#statNewMembers').text(stats.new_members_this_month ?? 0);
            $('#statBorrowingsMonth').text(stats.borrowings_this_month ?? 0);

            const ctx = document.getElementById('borrowingsChart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['نشطة', 'متأخرة'],
                        datasets: [{
                            data: [
                                stats.active_borrowings ?? 0,
                                stats.overdue_borrowings ?? 0,
                            ],
                            backgroundColor: ['#28a745', '#dc3545'],
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });
            }
        }).catch(function (error) {
            LmsHelpers.handleApiError(error);
        });

        LmsApi.getReportMostBorrowed().then(function (res) {
            const books = res.data?.books || [];
            if (!books.length) {
                $('#mostBorrowedBody').html('<tr><td colspan="3" class="empty-state">لا توجد بيانات</td></tr>');
                return;
            }
            let html = '';
            books.forEach(function (book, i) {
                html += '<tr><td>' + (i + 1) + '</td><td>' + (book.title || '-') + '</td><td>' + (book.borrow_count || 0) + '</td></tr>';
            });
            $('#mostBorrowedBody').html(html);
        });
    });
})();
