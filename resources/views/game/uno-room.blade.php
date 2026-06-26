@extends('layouts.app')
@section('title', 'UNO - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:10px; padding-bottom:0;">
    <h1 style="font-size:19px;">🎴 UNO</h1>
</div>

<div id="unoRoomRoot"><p style="text-align:center; color:var(--text-soft);">Memuatkan bilik...</p></div>

<div id="unoColorModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3 style="margin:0 0 14px;">Pilih Warna</h3>
        <div class="uno-color-pick">
            <button class="uno-color-btn" style="background:#e74c3c;" data-color="red"></button>
            <button class="uno-color-btn" style="background:#f1c40f;" data-color="yellow"></button>
            <button class="uno-color-btn" style="background:#2ecc71;" data-color="green"></button>
            <button class="uno-color-btn" style="background:#3498db;" data-color="blue"></button>
        </div>
    </div>
</div>

<div id="unoEndModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div id="unoEndIcon" style="font-size:40px;"></div>
        <h3 id="unoEndTitle" style="margin:8px 0;"></h3>
        <a href="{{ route('game.uno.menu') }}" class="btn" style="width:100%; display:block; text-align:center; text-decoration:none;">Kembali ke Menu</a>
    </div>
</div>

<style>
    .uno-mp-players { display:flex; gap:10px; overflow-x:auto; padding:6px 4px 14px; }
    .uno-mp-chip { flex-shrink:0; text-align:center; width:64px; }
    .uno-mp-chip .av { width:46px; height:46px; border-radius:50%; margin:0 auto 4px; display:flex; align-items:center; justify-content:center; font-size:20px; background:var(--grad-plum); color:#fff; background-size:cover; }
    .uno-mp-chip.active .av { box-shadow:0 0 0 3px var(--pink); }
    .uno-mp-chip .nm { font-size:10.5px; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .uno-mp-chip .ct { font-size:10px; color:var(--text-soft); }
    .uno-mp-chip .badge-status { font-size:10px; padding:2px 6px; border-radius:8px; background:var(--lilac-light); color:var(--plum); }

    .badge-status { font-size:11px; font-weight:700; padding:3px 9px; border-radius:10px; background:var(--lilac-light); color:var(--plum); flex-shrink:0; }

    .uno-log { font-size:11.5px; color:var(--text-soft); max-height:70px; overflow-y:auto; padding:6px 14px; margin-bottom:6px; }
    .uno-log div { padding:2px 0; }

    .uno-bot-row { display:flex; align-items:center; gap:10px; padding:10px 4px; }
    .uno-bot-avatar { width:42px; height:42px; border-radius:50%; background:var(--grad-plum); display:flex; align-items:center; justify-content:center; font-size:20px; }
    .uno-bot-name { font-weight:700; font-size:14px; }
    .uno-bot-cards { font-size:12px; color:var(--text-soft); }

    .uno-table { display:flex; justify-content:center; gap:30px; padding:14px 0; }
    .uno-pile { text-align:center; }
    .uno-pile-label { font-size:11px; color:var(--text-soft); margin-top:6px; }

    .uno-card {
        width:64px; height:92px; border-radius:10px; display:flex; align-items:center; justify-content:center;
        font-size:26px; font-weight:800; color:#fff; box-shadow:0 4px 10px rgba(0,0,0,0.18);
        border: 3px solid #fff; user-select:none; position:relative;
    }
    .uno-back { background: linear-gradient(135deg, #222 0%, #555 100%); cursor:pointer; font-size:22px; }
    .uno-back:active { transform: scale(0.96); }

    .uno-card.c-red { background:#e74c3c; }
    .uno-card.c-yellow { background:#f1c40f; color:#3a2c00; }
    .uno-card.c-green { background:#2ecc71; }
    .uno-card.c-blue { background:#3498db; }
    .uno-card.c-black { background:#222; }

    .uno-status { text-align:center; font-size:13px; font-weight:700; color:var(--plum-deep); min-height:18px; margin-bottom:6px; }

    .uno-hand { display:flex; gap:8px; overflow-x:auto; padding:10px 6px 16px; }
    .uno-hand .uno-card { flex-shrink:0; cursor:pointer; transition: transform .12s; width:54px; height:80px; font-size:20px; }
    .uno-hand .uno-card:active { transform: translateY(-6px); }
    .uno-hand .uno-card.disabled { opacity:.4; cursor:not-allowed; }

    .uno-actions { display:flex; gap:10px; justify-content:center; padding:6px 16px 30px; flex-wrap:wrap; }
    .uno-actions .btn { flex:1; max-width:160px; }

    .uno-color-pick { display:flex; gap:12px; justify-content:center; }
    .uno-color-btn { width:54px; height:54px; border-radius:50%; border:3px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.2); cursor:pointer; }
</style>

<script>
(function () {
    const roomId = {{ $room->id }};
    const myUserId = {{ auth()->id() }};
    const isCreator = {{ $isCreator ? 'true' : 'false' }};
    const csrfToken = '{{ csrf_token() }}';

    const urls = {
        poll: '{{ route('game.uno.poll', $room->id) }}',
        start: '{{ route('game.uno.start', $room->id) }}',
        leave: '{{ route('game.uno.leave', $room->id) }}',
        play: '{{ route('game.uno.play', $room->id) }}',
        draw: '{{ route('game.uno.draw', $room->id) }}',
        pass: '{{ route('game.uno.pass', $room->id) }}',
        callUno: '{{ route('game.uno.call-uno', $room->id) }}',
    };

    const root = document.getElementById('unoRoomRoot');
    const colorModal = document.getElementById('unoColorModal');
    const endModal = document.getElementById('unoEndModal');
    let pendingPlayIndex = null;
    let lastSignature = '';
    let polling = false;

    function get(url) { return fetch(url, { headers: { Accept: 'application/json' } }).then(r => r.json()); }
    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body: JSON.stringify(body || {}),
        }).then(r => r.json());
    }

    function statusBadge(s) {
        return { joined: 'Sertai ✓', invited: 'Menunggu...', declined: 'Tolak', left: 'Keluar' }[s] || s;
    }

    function renderLobby(lobby) {
        const joined = lobby.players.filter(p => p.status === 'joined');
        const others = lobby.players.filter(p => p.status !== 'joined');

        let html = '<div class="card"><div class="section-title">Dalam Bilik (' + joined.length + '/' + lobby.max_players + ')</div>';
        joined.forEach(p => {
            html += '<div class="friend-item"><div class="avatar size-sm" style="background:var(--grad-plum); color:#fff;">' +
                (p.name ? p.name.charAt(0).toUpperCase() : '?') + '</div><div class="meta"><div class="name">' +
                escapeHtml(p.name || ('Pemain #' + p.user_id)) + (p.is_creator ? ' 👑' : '') + '</div></div></div>';
        });
        html += '</div>';

        if (others.length) {
            html += '<div class="card"><div class="section-title">Belum Sertai</div>';
            others.forEach(p => {
                html += '<div class="friend-item"><div class="meta"><div class="name">' + escapeHtml(p.name || ('Pemain #' + p.user_id)) +
                    '</div></div><span class="badge-status">' + statusBadge(p.status) + '</span></div>';
            });
            html += '</div>';
        }

        html += '<div class="uno-actions">';
        if (isCreator) {
            const canStart = joined.length >= 2;
            html += '<button class="btn" id="startBtn" ' + (canStart ? '' : 'disabled style="opacity:.5;"') + '>Mula Permainan</button>';
        } else {
            html += '<p style="text-align:center; width:100%; color:var(--text-soft); font-size:13px;">Menunggu pengasas mula permainan...</p>';
            html += '<button class="btn secondary" id="leaveBtn">Keluar dari Bilik</button>';
        }
        html += '</div>';

        root.innerHTML = html;

        const startBtn = document.getElementById('startBtn');
        if (startBtn) startBtn.addEventListener('click', () => {
            fetch(urls.start, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } }).finally(refreshNow);
        });
        const leaveBtn = document.getElementById('leaveBtn');
        if (leaveBtn) leaveBtn.addEventListener('click', () => {
            fetch(urls.leave, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } }).then(() => window.location.href = '{{ route('game.uno.menu') }}');
        });
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : s;
        return d.innerHTML;
    }

    function canPlay(card, top, currentColor) {
        if (card.type === 'wild' || card.type === 'wild4') return true;
        return card.color === currentColor || card.value === top.value;
    }

    function renderGame(game) {
        if (root.querySelector('.uno-mp-players') === null) {
            root.innerHTML =
                '<div class="uno-mp-players" id="unoPlayers"></div>' +
                '<div class="uno-status" id="unoStatus"></div>' +
                '<div class="uno-table">' +
                    '<div class="uno-pile"><div class="uno-card uno-back" id="drawPile" title="Tarik kad">🎴</div><div class="uno-pile-label">Tarik (<span id="deckCount"></span>)</div></div>' +
                    '<div class="uno-pile"><div class="uno-card" id="discardCard"></div><div class="uno-pile-label">Atas</div></div>' +
                '</div>' +
                '<div class="uno-log" id="unoLog"></div>' +
                '<div class="uno-hand" id="unoHand"></div>' +
                '<div class="uno-actions" id="unoActions"></div>';

            document.getElementById('drawPile').addEventListener('click', () => {
                post(urls.draw, {}).then(handleActionResult);
            });
        }

        const playersEl = document.getElementById('unoPlayers');
        playersEl.innerHTML = '';
        game.players.forEach(p => {
            const chip = document.createElement('div');
            chip.className = 'uno-mp-chip' + (p.user_id === game.currentPlayerId ? ' active' : '');
            const avHtml = p.has_photo
                ? '<div class="av" style="background-image:url(\'' + p.photo + '\')"></div>'
                : '<div class="av">' + (p.avatar || '🙂') + '</div>';
            const isMe = p.user_id === myUserId;
            const calledUno = game.calledUno.includes(p.user_id) ? ' 📢' : '';
            chip.innerHTML = avHtml +
                '<div class="nm">' + escapeHtml(isMe ? 'Anda' : p.name) + '</div>' +
                '<div class="ct">' + (game.handCounts[p.user_id] ?? '-') + ' kad' + calledUno + '</div>';
            playersEl.appendChild(chip);
        });

        const statusEl = document.getElementById('unoStatus');
        if (game.status === 'finished') {
            statusEl.textContent = '';
        } else if (game.currentPlayerId === myUserId) {
            statusEl.textContent = game.drawnThisTurn ? 'Anda boleh main kad yang ditarik, atau pass.' : 'Giliran anda!';
        } else {
            const cur = game.players.find(p => p.user_id === game.currentPlayerId);
            statusEl.textContent = 'Menunggu ' + (cur ? cur.name : 'pemain lain') + '...';
        }

        document.getElementById('deckCount').textContent = game.deckCount;

        const discardEl = document.getElementById('discardCard');
        const top = game.discardTop;
        if (top) {
            discardEl.className = 'uno-card c-' + (top.type.startsWith('wild') ? game.currentColor : top.color);
            discardEl.textContent = labelFor(top);
        }

        const logEl = document.getElementById('unoLog');
        logEl.innerHTML = game.log.map(l => '<div>' + escapeHtml(l) + '</div>').join('');
        logEl.scrollTop = logEl.scrollHeight;

        const handEl = document.getElementById('unoHand');
        handEl.innerHTML = '';
        const myTurn = game.currentPlayerId === myUserId && game.status === 'active';
        game.myHand.forEach((card, idx) => {
            const el = document.createElement('div');
            const playable = myTurn && top && canPlay(card, top, game.currentColor);
            el.className = 'uno-card c-' + card.color + (playable ? '' : ' disabled');
            el.textContent = labelFor(card);
            if (playable) {
                el.addEventListener('click', () => {
                    if (card.type === 'wild' || card.type === 'wild4') {
                        pendingPlayIndex = idx;
                        colorModal.style.display = 'flex';
                    } else {
                        post(urls.play, { card_index: idx }).then(handleActionResult);
                    }
                });
            }
            handEl.appendChild(el);
        });

        const actionsEl = document.getElementById('unoActions');
        actionsEl.innerHTML = '';
        if (myTurn && game.drawnThisTurn) {
            const passBtn = document.createElement('button');
            passBtn.className = 'btn secondary';
            passBtn.textContent = 'Pass';
            passBtn.addEventListener('click', () => post(urls.pass, {}).then(handleActionResult));
            actionsEl.appendChild(passBtn);
        }
        if (game.myHand.length === 1 && !game.calledUno.includes(myUserId)) {
            const unoBtn = document.createElement('button');
            unoBtn.className = 'btn';
            unoBtn.textContent = '📢 UNO!';
            unoBtn.addEventListener('click', () => post(urls.callUno, {}).then(handleActionResult));
            actionsEl.appendChild(unoBtn);
        }

        if (game.status === 'finished') {
            const won = game.winnerId === myUserId;
            document.getElementById('unoEndIcon').textContent = won ? '🎉' : '😔';
            document.getElementById('unoEndTitle').textContent = won ? 'Anda Menang!' : ((game.players.find(p => p.user_id === game.winnerId)?.name || 'Pemain lain') + ' Menang');
            endModal.style.display = 'flex';
        }
    }

    function labelFor(card) {
        if (card.type === 'wild') return '🌈';
        if (card.type === 'wild4') return '+4';
        if (card.type === 'skip') return '🚫';
        if (card.type === 'reverse') return '🔁';
        if (card.type === 'draw2') return '+2';
        return card.value;
    }

    document.querySelectorAll('.uno-color-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            colorModal.style.display = 'none';
            if (pendingPlayIndex !== null) {
                post(urls.play, { card_index: pendingPlayIndex, chosen_color: btn.dataset.color }).then(handleActionResult);
                pendingPlayIndex = null;
            }
        });
    });

    function handleActionResult(data) {
        if (data.error) {
            alert(data.error);
            refreshNow();
            return;
        }
        applyPollResult(data);
    }

    function applyPollResult(data) {
        if (data.status === 'waiting') {
            renderLobby(data.lobby);
        } else if (data.game) {
            renderGame(data.game);
        }
    }

    function refreshNow() {
        get(urls.poll).then(applyPollResult);
    }

    function pollLoop() {
        if (polling) return;
        polling = true;
        get(urls.poll).then(data => {
            const sig = JSON.stringify(data);
            if (sig !== lastSignature) {
                lastSignature = sig;
                applyPollResult(data);
            }
            polling = false;
            if (data.status !== 'finished') {
                setTimeout(pollLoop, 1800);
            }
        }).catch(() => {
            polling = false;
            setTimeout(pollLoop, 1800);
        });
    }

    pollLoop();
})();
</script>

@endsection
