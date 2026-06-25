<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#ff5d8f">
    <title>@yield('title', 'Carian Jodoh')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="bg-blob one"></div>
    <div class="bg-blob two"></div>

    @auth
    <div class="topbar">
        <div class="brand"><span class="brand-emoji">💕</span> Carian Jodoh</div>
        <div class="topbar-actions">
            <span class="credit-badge">💎 {{ auth()->user()->credits }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-btn" type="submit">Keluar</button>
            </form>
        </div>
    </div>
    @endauth

    <div class="wrap">
        @if (session('status'))
            <div class="alert success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </div>

    @auth
    <div class="bottom-nav">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="ic">🏠</span>Utama
        </a>
        <a href="{{ route('friends.index') }}" class="{{ request()->routeIs('friends.*') ? 'active' : '' }}">
            <span class="ic">
                👥
                @if(($pendingRequestsCount ?? auth()->user()->receivedFriendRequests()->where('status','pending')->count()) > 0)
                    <span class="notif-dot"></span>
                @endif
            </span>Kawan
        </a>
        <a href="{{ route('payment.index') }}" class="{{ request()->routeIs('payment.*') ? 'active' : '' }}">
            <span class="ic">💎</span>Credit
        </a>
        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <span class="ic">🙍</span>Profil
        </a>
    </div>
    @endauth
    @stack('scripts')
</body>
</html>
