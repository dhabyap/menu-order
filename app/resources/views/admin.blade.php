<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Pengelola · {{ $storeName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}">
</head>
<body>
<div class="shell">
    @include('admin.partials.header')

    <main class="admin-shell">
        @include('admin.partials.intro')
        @include('admin.partials.stats')
        @include('admin.partials.menu-table')
        @include('admin.partials.settings')
        @include('admin.partials.orders')
        @include('admin.partials.activity-log')
    </main>
</div>
@include('admin.partials.add-menu-dialog')
<script src="{{ asset('assets/admin/admin.js') }}"></script>
</body>
</html>
