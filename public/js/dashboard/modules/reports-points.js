(function () {
    'use strict';

    function setText(id, value) {
        document.getElementById(id).textContent = Number(value || 0).toLocaleString('ar');
    }

    function runDownload(button, download) {
        button.disabled = true;
        download()
            .catch(LmsHelpers.handleApiError)
            .finally(function () {
                button.disabled = false;
            });
    }

    runWhenDashboardReady(function () {
        if (!document.getElementById('pointsSummaryReport')) return;

        LmsApi.getReportPointsSummary().then(function (response) {
            const data = response.data || {};
            setText('pointsTotalBalance', data.total_balance_all_users);
            setText('pointsTotalTopUps', data.total_top_ups);
            setText('pointsTotalSpent', data.total_spent);
            setText('pointsTotalRewards', data.total_rewards);
            setText('pointsCodesUnused', data.codes_unused);
            setText('pointsCodesUsed', data.codes_used);
        }).catch(LmsHelpers.handleApiError);

        document.getElementById('pointsExportButton').addEventListener('click', function () {
            const button = this;
            const params = {
                from: document.getElementById('pointsExportFrom').value || undefined,
                to: document.getElementById('pointsExportTo').value || undefined,
                type: document.getElementById('pointsExportType').value || undefined,
            };
            runDownload(button, function () {
                return LmsApi.downloadPointsExport(params);
            });
        });

        document.getElementById('finesExportButton').addEventListener('click', function () {
            const button = this;
            runDownload(button, function () {
                return LmsApi.downloadFinesExport();
            });
        });

        document.getElementById('overdueExportButton').addEventListener('click', function () {
            const button = this;
            runDownload(button, function () {
                return LmsApi.downloadOverdueExport();
            });
        });
    });
})();
