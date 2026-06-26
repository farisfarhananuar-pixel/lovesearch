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

            <div class="notif-bell-wrap" id="notifBellWrap">
                <button type="button" class="notif-bell-btn" id="notifBellBtn" title="Notifikasi">
                    🔔
                    <span class="notif-dot" id="notifBadge" style="display:none;"></span>
                </button>
                <div class="notif-dropdown" id="notifDropdown" style="display:none;">
                    <div class="notif-dropdown-header">
                        Notifikasi
                        <form method="POST" action="{{ route('notifications.read-all') }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="notif-mark-all">Tandakan dah baca</button>
                        </form>
                    </div>
                    <div id="notifList">
                        <div class="notif-empty">Tiada notifikasi setakat ini.</div>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="notif-see-all">Lihat semua</a>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-btn" type="submit">Keluar</button>
            </form>
        </div>
    </div>
    @endauth

    @auth
    <style>
        .notif-bell-wrap { position: relative; }
        .notif-bell-btn {
            background: none; border: none; font-size: 18px; cursor: pointer;
            position: relative; padding: 4px 2px; line-height: 1;
        }
        .notif-bell-btn .notif-dot {
            position: absolute; top: -2px; right: -2px;
        }
        .notif-dropdown {
            position: absolute; right: 0; top: calc(100% + 8px);
            width: 280px; max-height: 360px; overflow-y: auto;
            background: var(--card-bg, #fff); border-radius: 14px;
            box-shadow: 0 8px 28px rgba(91,42,134,0.18); z-index: 50;
        }
        .notif-dropdown-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 14px; font-weight: 700; font-size: 13px;
            border-bottom: 1px solid var(--border, #eee);
        }
        .notif-mark-all {
            background: none; border: none; color: var(--plum, #5b2a86);
            font-size: 11px; cursor: pointer; padding: 0;
        }
        .notif-item {
            display: block; padding: 10px 14px; text-decoration: none; color: inherit;
            border-bottom: 1px solid var(--border, #f3f3f3);
        }
        .notif-item.unread { background: rgba(255,93,143,0.07); }
        .notif-item .t { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
        .notif-item .b { font-size: 12px; color: var(--text-soft); }
        .notif-item .time { font-size: 11px; color: var(--text-soft); margin-top: 3px; display: block; }
        .notif-empty { padding: 18px 14px; font-size: 12px; color: var(--text-soft); text-align: center; }
        .notif-see-all {
            display: block; text-align: center; padding: 10px; font-size: 12px;
            font-weight: 700; color: var(--plum, #5b2a86); text-decoration: none;
        }
    </style>
    <script>
        (function () {
            var bellBtn = document.getElementById('notifBellBtn');
            var dropdown = document.getElementById('notifDropdown');
            var badge = document.getElementById('notifBadge');
            var list = document.getElementById('notifList');
            var pollUrl = '{{ route('notifications.poll') }}';

            bellBtn.addEventListener('click', function () {
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
            });
            document.addEventListener('click', function (e) {
                if (!document.getElementById('notifBellWrap').contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });

            function escapeHtml(str) {
                var d = document.createElement('div');
                d.innerText = str || '';
                return d.innerHTML;
            }

            function renderList(items) {
                if (!items.length) {
                    list.innerHTML = '<div class="notif-empty">Tiada notifikasi setakat ini.</div>';
                    return;
                }
                list.innerHTML = items.map(function (n) {
                    var href = n.link || '{{ route('notifications.index') }}';
                    return '<a href="' + href + '" class="notif-item ' + (n.read ? '' : 'unread') + '">' +
                        '<div class="t">' + escapeHtml(n.title) + '</div>' +
                        (n.body ? '<div class="b">' + escapeHtml(n.body) + '</div>' : '') +
                        '<span class="time">' + escapeHtml(n.time) + '</span>' +
                        '</a>';
                }).join('');
            }

            function poll() {
                fetch(pollUrl, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.unread_count > 0) {
                            badge.style.display = 'block';
                        } else {
                            badge.style.display = 'none';
                        }
                        renderList(data.latest);
                        setTimeout(poll, 15000);
                    })
                    .catch(function () { setTimeout(poll, 20000); });
            }
            poll();
        })();
    </script>
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
        <a href="{{ route('game.hub') }}" class="{{ request()->routeIs('game.*') ? 'active' : '' }}">
            <span class="ic">🎮</span>Game
        </a>
        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <span class="ic">🙍</span>Profil
        </a>
    </div>
    @endauth
    @stack('scripts')
</body>
</html>
