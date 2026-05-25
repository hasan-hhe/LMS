<script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/datatables/datatables.rtl.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sweetalert/sweetalert.rtl.min.js') }}"></script>
<script src="{{ asset('assets/js/kaiadmin.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    window.LMS_ROUTES = {
        login: @json(route('admin.login')),
        dashboard: @json(route('admin.dashboard')),
        booksIndex: @json(route('admin.books.index')),
        authorsIndex: @json(route('admin.authors.index')),
        categoriesIndex: @json(route('admin.categories.index')),
        publishersIndex: @json(route('admin.publishers.index')),
        bookInstancesIndex: @json(route('admin.book-instances.index')),
        membersIndex: @json(route('admin.members.index')),
        borrowingsIndex: @json(route('admin.borrowings.index')),
        finesIndex: @json(route('admin.fines.index')),
        reservationsIndex: @json(route('admin.reservations.index')),
        ordersIndex: @json(route('admin.orders.index')),
        reportsOverdue: @json(route('admin.reports.overdue')),
    };

    window.LMS_LOOKUPS = {
        instanceStates: @json($instanceStates ?? []),
        orderStates: @json($orderStates ?? []),
    };
</script>

<script src="{{ asset('js/dashboard/api.js') }}"></script>
<script src="{{ asset('js/dashboard/helpers.js') }}"></script>
<script src="{{ asset('js/dashboard/auth.js') }}"></script>

<script>
    function initDataTable(table) {
        if (!$.fn.DataTable || !table) return;

        const $table = table && table.jquery ? table : $(table);
        if (!$table.length || !$table.is('table')) return;

        if ($.fn.DataTable.isDataTable($table[0])) {
            $table.DataTable().clear().destroy();
        }

        $table.DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: true,
        });
    }

    function confirmDelete(message, onConfirm) {
        swal(message || 'هل أنت متأكد من الحذف؟', {
            icon: 'warning',
            buttons: {
                cancel: { text: 'إلغاء', visible: true, className: 'btn btn-secondary' },
                confirm: { text: 'حذف', className: 'btn btn-danger' },
            },
            dangerMode: true,
        }).then(function (confirmed) {
            if (confirmed && typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }

    window.runWhenDashboardReady = function (callback) {
        const run = function () {
            if (typeof callback === 'function') {
                callback();
            }
        };

        const start = function () {
            if (document.body.dataset.requireAuth === 'true' && window.LmsAuthReady) {
                window.LmsAuthReady.then(run).catch(function () {});
                return;
            }
            run();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
    };

</script>

@stack('scripts')
