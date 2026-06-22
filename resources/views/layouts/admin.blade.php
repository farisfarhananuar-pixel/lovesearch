<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="admin-body">
    @auth('admin')
    <div class="admin-topbar">
        <div class="brand">💕 Admin - Carian Jodoh</div>
        <div style="display:flex;gap:16px;align-items:center;">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.payments') }}">Pembayaran</a>
            <a href="{{ route('admin.settings') }}">Settings</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Log Keluar</button>
            </form>
        </div>
    </div>
    @endauth

    <div class="admin-wrap">
        @if (session('status'))
            <div class="alert success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $e)
                    {{ $e }}<br>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
