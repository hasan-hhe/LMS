(function () {
    'use strict';

    const finesFilterConfig = {
        fields: {
            is_paid: '#filterFinePaid',
        },
    };

    function getFinesListParams(page) {
        return LmsHelpers.buildListParams(page, finesFilterConfig);
    }

    function loadFinesList(page) {
        return LmsHelpers.loadPaginatedTable({
            apiCall: LmsApi.getFines,
            getParams: getFinesListParams,
            params: getFinesListParams(page || 1),
            tableBodySelector: '#finesTableBody',
            paginationSelector: '#finesPagination',
            totalSelector: '#totalFines',
            renderRow: function (fine, index, meta) {
                const rowNum = ((meta.current_page || 1) - 1) * (meta.per_page || 15) + index + 1;
                const status = fine.is_paid
                    ? '<span class="badge bg-success">مدفوعة</span>'
                    : '<span class="badge bg-warning text-dark">غير مدفوعة</span>';
                const payButton = fine.is_paid
                    ? ''
                    : '<button type="button" class="btn btn-sm btn-success btn-pay-fine" data-id="' + fine.id + '"><i class="fa fa-money-bill"></i> دفع</button>';
                return '<tr>' +
                    '<td>' + rowNum + '</td>' +
                    '<td>' + (fine.borrowing?.member?.full_name || '-') + '</td>' +
                    '<td>' + (fine.days_late || 0) + '</td>' +
                    '<td>' + (fine.fine || 0) + '</td>' +
                    '<td>' + status + '</td>' +
                    '<td>' + LmsHelpers.formatDate(fine.paid_at) + '</td>' +
                    '<td>' + payButton + '</td>' +
                    '</tr>';
            },
        });
    }

    runWhenDashboardReady(function () {
        if (!document.getElementById('finesTableBody')) return;

        loadFinesList(1);
        LmsHelpers.bindTableFilters(finesFilterConfig, loadFinesList);

        $(document).on('click', '.btn-pay-fine', function () {
            const id = $(this).data('id');
            swal('هل أنت متأكد من تسجيل دفع هذه الغرامة؟', {
                icon: 'warning',
                buttons: {
                    cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                    confirm: { text: 'تأكيد الدفع', className: 'btn btn-success' },
                },
            }).then(function (confirmed) {
                if (!confirmed) return;
                LmsApi.payFine(id).then(function (res) {
                    LmsHelpers.notify('success', LmsHelpers.responseMessage(res));
                    loadFinesList(1);
                }).catch(LmsHelpers.handleApiError);
            });
        });
    });
})();
