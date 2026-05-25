(function () {
    'use strict';

    function formatNumber(value, decimals) {
        if (value === null || value === undefined || value === '') return '-';
        const num = parseFloat(value);
        if (isNaN(num)) return value;
        if (decimals !== undefined) {
            return num.toFixed(decimals);
        }
        return num;
    }

    function initOverdueReport() {
        const body = document.getElementById('overdueReportBody');
        if (!body) return;

        LmsApi.getReportOverdue().then(function (res) {
            const data = res.data || {};
            const borrowings = data.borrowings || [];

            $('#overdueTotal').text('إجمالي المتأخرة: ' + (data.total ?? borrowings.length));

            if (!borrowings.length) {
                LmsHelpers.showEmpty('#overdueReportBody', 'لا توجد استعارات متأخرة');
                return;
            }

            let html = '';
            borrowings.forEach(function (item, i) {
                html += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + (item.member || '-') + '</td>' +
                    '<td>' + (item.book_title || '-') + '</td>' +
                    '<td>' + LmsHelpers.formatDate(item.end_date) + '</td>' +
                    '<td>' + (item.days_overdue ?? 0) + '</td>' +
                    '</tr>';
            });
            $(body).html(html);
            initDataTable($(body).closest('table')[0]);
        }).catch(function (error) {
            if (error && error.response) {
                LmsHelpers.handleApiError(error);
            }
            LmsHelpers.showEmpty('#overdueReportBody', 'تعذر تحميل البيانات');
        });
    }

    function initMostBorrowedReport() {
        const body = document.getElementById('mostBorrowedReportBody');
        if (!body) return;

        LmsApi.getReportMostBorrowed().then(function (res) {
            const books = res.data?.books || [];

            if (!books.length) {
                LmsHelpers.showEmpty('#mostBorrowedReportBody', 'لا توجد بيانات');
                return;
            }

            let html = '';
            books.forEach(function (book, i) {
                html += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + (book.ISBN || book.isbn || '-') + '</td>' +
                    '<td>' + (book.title || '-') + '</td>' +
                    '<td>' + (book.borrow_count || 0) + '</td>' +
                    '</tr>';
            });
            $(body).html(html);
            initDataTable($(body).closest('table')[0]);
        }).catch(function (error) {
            if (error && error.response) {
                LmsHelpers.handleApiError(error);
            }
            LmsHelpers.showEmpty('#mostBorrowedReportBody', 'تعذر تحميل البيانات');
        });
    }

    function initFinesSummaryReport() {
        if (!document.getElementById('finesSummaryReport')) return;

        LmsApi.getReportFinesSummary().then(function (res) {
            const data = res.data || {};
            $('#finesTotalCount').text(formatNumber(data.total_fines));
            $('#finesTotalAmount').text(formatNumber(data.total_amount, 2));
            $('#finesPaidAmount').text(formatNumber(data.paid_amount, 2));
            $('#finesUnpaidAmount').text(formatNumber(data.unpaid_amount, 2));
            $('#finesPaidCount').text(formatNumber(data.paid_count));
            $('#finesUnpaidCount').text(formatNumber(data.unpaid_count));
            $('#finesAvgDaysLate').text(formatNumber(data.avg_days_late, 1));
        }).catch(LmsHelpers.handleApiError);
    }

    function initInventoryReport() {
        if (!document.getElementById('inventoryReport')) return;

        LmsApi.getReportInventory().then(function (res) {
            const data = res.data || {};
            $('#invTotalBooks').text(formatNumber(data.total_books));
            $('#invTotalInstances').text(formatNumber(data.total_instances));
            $('#invAvailableInstances').text(formatNumber(data.available_instances));
            $('#invBorrowedInstances').text(formatNumber(data.borrowed_instances));
            $('#invReservedInstances').text(formatNumber(data.reserved_instances));
            $('#invDamagedInstances').text(formatNumber(data.damaged_instances));
            $('#invLostInstances').text(formatNumber(data.lost_instances));
            $('#invTotalMembers').text(formatNumber(data.total_members));
            $('#invExpiredMemberships').text(formatNumber(data.expired_memberships));
        }).catch(LmsHelpers.handleApiError);
    }

    runWhenDashboardReady(function () {
        initOverdueReport();
        initMostBorrowedReport();
        initFinesSummaryReport();
        initInventoryReport();
    });
})();
