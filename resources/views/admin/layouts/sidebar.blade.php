<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <div class="logo-header" data-background-color="dark">
            <a href="{{ route('admin.dashboard') }}" class="logo d-none d-lg-flex align-items-center gap-2">
                <img src="{{ asset('assets/img/libralms-mark.png') }}" alt="LibraLMS" class="navbar-brand libralms-mark">
                <span class="navbar-brand text-white fw-bold libralms-wordmark">LibraLMS</span>
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner mt-5">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-item {{ Route::is('admin.dashboard') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-home"></i>
                        <p>لوحة التحكم</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">الفهرسة</h4>
                </li>

                <li class="nav-item {{ Route::is('admin.books.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.books.index') }}">
                        <i class="fas fa-book"></i>
                        <p>الكتب</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.book-instances.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.book-instances.index') }}">
                        <i class="fas fa-copy"></i>
                        <p>نسخ الكتب</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.authors.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.authors.index') }}">
                        <i class="fas fa-user-edit"></i>
                        <p>المؤلفون</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.categories.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.categories.index') }}">
                        <i class="fas fa-tags"></i>
                        <p>التصنيفات</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.publishers.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.publishers.index') }}">
                        <i class="fas fa-building"></i>
                        <p>دور النشر</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">الأعضاء</h4>
                </li>

                <li class="nav-item {{ Route::is('admin.members.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.members.index') }}">
                        <i class="fas fa-users"></i>
                        <p>الأعضاء</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.notifications') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.notifications') }}">
                        <i class="fas fa-bell"></i>
                        <p>الإشعارات</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.librarians.*') ? 'active' : '' }}" data-role="ADMIN">
                    <a href="{{ route('admin.librarians.index') }}">
                        <i class="fas fa-user-tie"></i>
                        <p>أمناء المكتبة</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">الإعارة</h4>
                </li>

                <li class="nav-item {{ Route::is('admin.borrowings.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.borrowings.index') }}">
                        <i class="fas fa-exchange-alt"></i>
                        <p>الاستعارات</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.reservations.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.reservations.index') }}">
                        <i class="fas fa-bookmark"></i>
                        <p>الحجوزات</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.fines.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.fines.index') }}">
                        <i class="fas fa-money-bill-wave"></i>
                        <p>الغرامات</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">المشتريات</h4>
                </li>

                <li class="nav-item {{ Route::is('admin.orders.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.orders.index') }}">
                        <i class="fas fa-shopping-cart"></i>
                        <p>الطلبات</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.reviews.*') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.reviews.index') }}">
                        <i class="fas fa-star"></i>
                        <p>التقييمات</p>
                    </a>
                </li>

                <li class="nav-section" data-role="ADMIN,LIBRARIAN">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">النقاط</h4>
                </li>

                <li class="nav-item {{ Route::is('admin.points.top-up-codes') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.points.top-up-codes') }}">
                        <i class="fas fa-ticket-alt"></i>
                        <p>أكواد الشحن</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.points.settings') ? 'active' : '' }}" data-role="ADMIN">
                    <a href="{{ route('admin.points.settings') }}">
                        <i class="fas fa-cog"></i>
                        <p>إعدادات النقاط</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">التقارير</h4>
                </li>

                <li class="nav-item {{ Route::is('admin.reports.overdue') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.reports.overdue') }}">
                        <i class="fas fa-clock"></i>
                        <p>المتأخرة</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.reports.most-borrowed') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.reports.most-borrowed') }}">
                        <i class="fas fa-chart-bar"></i>
                        <p>الأكثر استعارة</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.reports.fines-summary') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.reports.fines-summary') }}">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <p>ملخص الغرامات</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.reports.inventory') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.reports.inventory') }}">
                        <i class="fas fa-warehouse"></i>
                        <p>جرد المخزون</p>
                    </a>
                </li>

                <li class="nav-item {{ Route::is('admin.reports.points') ? 'active' : '' }}" data-role="ADMIN,LIBRARIAN">
                    <a href="{{ route('admin.reports.points') }}">
                        <i class="fas fa-coins"></i>
                        <p>اقتصاد النقاط</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
