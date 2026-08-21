<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'LibraLMS')</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset('assets/img/libralms-mark.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/fonts.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins.rtl.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/kaiadmin.rtl.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

    <style>
        :root {
            --lms-primary: #1F4E79;
            --lms-primary-dark: #163a5c;
        }

        .alert, .brand, .btn-simple, .h1, .h2, .h3, .h4, .h5, .h6,
        .navbar, .td-name, a, body, button.close, h1, h2, h3, h4, h5, h6, p, td {
            font-family: 'Tajawal', sans-serif !important;
        }

        .sidebar[data-background-color="dark"],
        .logo-header[data-background-color="dark"] {
            background: var(--lms-primary) !important;
        }

        .logo-header .logo .navbar-brand.libralms-mark,
        .logo-header .logo img.libralms-mark {
            margin: 0 !important;
            height: 42px;
            width: auto;
            padding: 0;
        }

        .logo-header .logo .navbar-brand.libralms-wordmark {
            margin: 0 !important;
            padding: 0;
            font-size: 1.15rem;
            letter-spacing: 0.02em;
        }

        .btn-primary {
            background-color: var(--lms-primary) !important;
            border-color: var(--lms-primary) !important;
        }

        .btn-primary:hover {
            background-color: var(--lms-primary-dark) !important;
            border-color: var(--lms-primary-dark) !important;
        }

        button.btn[data-busy="1"],
        .btn[data-busy="1"] {
            pointer-events: none;
            opacity: 0.8;
            cursor: wait;
        }

        .nav-item.active > a,
        .nav-item.active > a p,
        .nav-item.active > a i {
            color: #fff !important;
        }

        .table thead th {
            padding: 0 10px 10px 0 !important;
            white-space: nowrap;
            text-align: right;
        }

        .table td {
            text-align: right;
        }

        .page-loading {
            text-align: center;
            padding: 2rem;
            color: #888;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

        .stat-card .card-body {
            padding: 1.25rem;
        }

        .stat-card h4 {
            margin-bottom: 0;
            font-weight: 700;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--lms-primary) 0%, var(--lms-primary-dark) 100%);
            min-height: 100vh;
        }

        .lms-file-input-group .btn-preview-file {
            white-space: nowrap;
        }

        .choices {
            margin-bottom: 0;
        }

        .libralms-login-logo {
            display: block;
            width: 180px;
            max-width: 70%;
            height: auto;
            margin-inline: auto;
        }
    </style>

    @stack('styles')
</head>
