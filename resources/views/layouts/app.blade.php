<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>@yield('title', 'Carian Jodoh')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @auth
    <div class="topbar">
        <div class="brand">💕 Carian Jodoh</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout-btn" type="submit">Log Keluar</button>
        </form>
    </div>
    @endauth

    <div class="wrap">
        @if (session('status'))
            <div class="alert success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>

    @auth
    <div class="bottom-nav">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="ic">🏠</span>Utama
        </a>
        <a href="{{ route('payment.index') }}" class="{{ request()->routeIs('payment.*') ? 'active' : '' }}">
            <span class="ic">💎</span>Beli Credit
        </a>
    </div>
    @endauth
    @stack('scripts')
</body>
</html>
