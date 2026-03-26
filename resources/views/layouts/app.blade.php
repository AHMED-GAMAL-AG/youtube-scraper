<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>YouTube Course Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-light bg-white border-bottom sticky-top">
        <div class="container d-flex justify-content-end">
            <div class="d-flex align-items-center gap-3">
                <span class="navbar-text text-muted">أداة جمع الدورات التعليمية</span>
                <span class="nav-separator"></span>
                <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="/">
                    <span class="brand-text">YouTube Course Scraper</span>
                    <span class="brand-icon"><i class="bi bi-play-fill"></i></span>
                </a>
            </div>
        </div>
    </nav>

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
