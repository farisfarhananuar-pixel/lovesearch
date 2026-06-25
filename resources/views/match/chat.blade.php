<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#ff5d8f">
    <title>{{ $revealed ? 'Sembang Kawan' : 'Sembang Misteri' }} - Carian Jodoh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="chat-header">
    @if ($match->status === 'ended' || $match->status === 'revealed')
        <a href="{{ route('dashboard') }}" class="chat-home-btn" title="Kembali ke Utama">🏠</a>
    @else
        <form method="POST" action="{{ route('match.leave', $match->id) }}" onsubmit="return confirm('Tamatkan sembang ini dan kembali ke Utama?');" style="display:flex; flex-shrink:0;">
            @csrf
            <button type="submit" class="chat-home-btn" title="Tamatkan sembang & kembali ke Utama" style="border:none; cursor:pointer;">🏠</button>
        </form>
    @endif

    <div class="avatar size-md" id="avatarBox" @if($revealed && $partner->profile_photo) style="background-image:url('{{ $partner->profile_photo }}')" @endif>
        @if (!$revealed)❓@elseif(!$partner->profile_photo){{ $partner->avatarFallback() }}@endif
    </div>

    <div class="who">
        <div class="name" id="partnerName">{{ $revealed ? $partner->displayName() : 'Misteri' }}</div>
        <div class="sub" id="partnerSub">{{ ucfirst($partner->gender) }} • {{ ucfirst($partner->race) }}</div>
    </div>

    @if ($match->status === 'revealed')
        <button type="button" class="chat-icon-btn plum" id="menuBtn" title="Lagi">⋮</button>
    @endif

    @if (!$revealed && $match->status !== 'ended')
        <div class="chat-timer" id="timerBox">02:00</div>
    @elseif ($match->status === 'revealed')
        <div class="chat-timer friend" id="timerBox">Kawan ⏳</div>
    @endif
</div>

<div id="friendMenu" style="display:none; background:#fff; padding:10px 16px; box-shadow:0 4px 14px rgba(91,42,134,0.1); position:relative; z-index:9;">
    @if ($match->isBlocked())
        @if ($match->blockedByUser($user))
            <form method="POST" action="{{ route('match.unblock', $match->id) }}">
                @csrf
                <button class="btn secondary block-small" type="submit">✅ Buka Sekatan</button>
            </form>
        @else
            <p style="font-size:12px; color:var(--text-soft); text-align:center; margin:6px 0;">Sembang ini sedang tidak tersedia.</p>
        @endif
    @else
        <form method="POST" action="{{ route('match.block', $match->id) }}" onsubmit="return confirm('Sekat kawan ini? Anda boleh buka sekatan bila-bila masa.');" style="margin-bottom:8px;">
            @csrf
            <button class="btn secondary block-small" type="submit">🚫 Sekat Kawan Ini</button>
        </form>
    @endif
    <form method="POST" action="{{ route('match.unfriend', $match->id) }}" onsubmit="return confirm('Buang kawan ini secara kekal? Anda perlu hantar permintaan semula untuk berkawan lagi.');">
        @csrf
        <button class="btn danger block-small" type="submit">🗑️ Buang Kawan</button>
    </form>
</div>

@if (!$revealed && $match->status !== 'ended')
<div class="love-banner" id="loveBanner">
    💡 Identiti dirahsiakan. Tekan ❤️ kalau anda suka - kalau kedua-dua tekan, kalian jadi kawan kekal!
</div>
@elseif ($match->status === 'revealed' && !$match->isBlocked())
<div class="friend-banner" id="friendBanner">
    🎉 Kalian kawan kekal sekarang - boleh sembang bila-bila masa tanpa had!
</div>
@endif

@if ($match->isBlocked())
<div class="block-banner" id="blockBanner">
    @if ($match->blockedByUser($user))
        🚫 Anda telah sekat sembang ini.
        <form method="POST" action="{{ route('match.unblock', $match->id) }}">
            @csrf
            <button type="submit">Buka Sekatan</button>
        </form>
    @else
        🚫 Sembang ini sedang tidak tersedia pada masa ini.
    @endif
</div>
@endif

<div class="chat-body" id="chatBody">
    <div class="system-note">Sembang bermula. Berbual elok-elok ya 😊</div>
    @foreach ($messages as $m)
        <div class="bubble {{ $m->sender_id === $user->id ? 'mine' : 'theirs' }}">
            {{ $m->body }}
            <span class="time">{{ $m->created_at->format('H:i') }}</span>
        </div>
    @endforeach
</div>

<div id="endedCard" style="display:none; padding:30px 18px 100px; text-align:center;">
    <div class="card ended-card">
        <div class="icon">⌛</div>
        <h2 style="color:var(--pink-dark);">Sesi Sembang Tamat</h2>
        <p style="color:var(--text-soft); font-size:14px;">Jangan risau, cuba lagi untuk dapatkan padanan baru!</p>
        <a class="btn" href="{{ route('dashboard') }}" style="margin-top:14px;">Kembali ke Utama</a>
    </div>
</div>

<div class="chat-footer" id="chatFooter" style="{{ ($match->status === 'ended' || $match->isBlocked()) ? 'display:none' : '' }}">
    @if ($match->status !== 'revealed')
        <button class="love {{ $match->lovedBy($user) ? 'active' : '' }}" id="loveBtn" title="Suka">❤️</button>
    @endif
    <input type="text" id="msgInput" placeholder="Taip mesej..." maxlength="500" autocomplete="off">
    <button class="send" id="sendBtn">➤</button>
</div>

@if ($match->status !== 'revealed' && $match->status !== 'ended')
<div style="text-align:center; padding:8px 16px 90px;">
    <form method="POST" action="{{ route('match.leave', $match->id) }}" onsubmit="return confirm('Tak nak sembang dengan orang ni? Sesi akan ditamatkan dan anda akan dibawa balik ke Utama.');">
        @csrf
        <button type="submit" style="background:none; border:none; color:var(--text-soft); font-size:12px; text-decoration:underline; cursor:pointer; padding:6px;">
            🚪 Tak nak sembang, tamatkan sesi ini
        </button>
    </form>
</div>
@endif

<script>
const pollUrl = '{{ route("match.poll", $match->id) }}';
const sendUrl = '{{ route("match.message", $match->id) }}';
const loveUrl = '{{ route("match.love", $match->id) }}';
const csrfToken = '{{ csrf_token() }}';

let lastId = {{ $messages->max('id') ?? 0 }};
let revealed = {{ $revealed ? 'true' : 'false' }};
let ended = {{ $match->status === 'ended' ? 'true' : 'false' }};
let blocked = {{ $match->isBlocked() ? 'true' : 'false' }};

const chatBody = document.getElementById('chatBody');
const timerBox = document.getElementById('timerBox');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const loveBtn = document.getElementById('loveBtn');
const endedCard = document.getElementById('endedCard');
const chatFooter = document.getElementById('chatFooter');
const loveBanner = document.getElementById('loveBanner');
const menuBtn = document.getElementById('menuBtn');
const friendMenu = document.getElementById('friendMenu');

if (menuBtn) {
    menuBtn.addEventListener('click', () => {
        friendMenu.style.display = friendMenu.style.display === 'none' ? 'block' : 'none';
    });
}

function scrollToBottom() {
    window.scrollTo(0, document.body.scrollHeight);
}
scrollToBottom();

function appendMessage(m) {
    const div = document.createElement('div');
    div.className = 'bubble ' + (m.mine ? 'mine' : 'theirs');
    div.innerHTML = escapeHtml(m.body) + '<span class="time">' + m.time + '</span>';
    chatBody.appendChild(div);
    scrollToBottom();
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.innerText = str;
    return d.innerHTML;
}

function showEnded() {
    ended = true;
    chatFooter.style.display = 'none';
    if (loveBanner) loveBanner.style.display = 'none';
    endedCard.style.display = 'block';
}

function applyReveal(data) {
    revealed = true;
    const avatarBox = document.getElementById('avatarBox');
    if (data.partner_photo) {
        avatarBox.style.backgroundImage = "url('" + data.partner_photo + "')";
        avatarBox.textContent = '';
    } else {
        avatarBox.textContent = data.partner_avatar_fallback || '😍';
    }
    document.getElementById('partnerName').textContent = data.partner_name;
    if (timerBox) {
        timerBox.textContent = 'Kawan ⏳';
        timerBox.classList.remove('urgent');
        timerBox.classList.add('friend');
    }
    if (loveBanner) loveBanner.style.display = 'none';
    if (loveBtn) loveBtn.style.display = 'none';
}

function sendMessage() {
    const body = msgInput.value.trim();
    if (!body || ended || blocked) return;
    msgInput.value = '';
    fetch(sendUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ body })
    }).then(r => r.json()).then(data => {
        if (data.error && !data.blocked) { showEnded(); }
    });
}

sendBtn.addEventListener('click', sendMessage);
msgInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });

if (loveBtn) {
    loveBtn.addEventListener('click', () => {
        if (ended) return;
        fetch(loveUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            loveBtn.classList.add('active');
        });
    });
}

function poll() {
    fetch(pollUrl + '?after_id=' + lastId, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            data.messages.forEach(m => {
                appendMessage(m);
                lastId = m.id;
            });

            if (data.revealed && !revealed) {
                applyReveal(data);
            }
            if (data.partner_loved && !revealed) {
                document.getElementById('partnerSub').textContent = 'Dia dah tekan ❤️ juga!';
            }
            if (!revealed && data.seconds_left !== null && timerBox) {
                const m = Math.floor(data.seconds_left / 60);
                const s = data.seconds_left % 60;
                timerBox.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                if (data.seconds_left <= 20) timerBox.classList.add('urgent');
            }
            if (data.blocked !== blocked) {
                blocked = data.blocked;
                location.reload();
                return;
            }
            if (data.status === 'ended' && !ended) {
                showEnded();
                return;
            }
            if (!ended) setTimeout(poll, 2000);
        })
        .catch(() => { if (!ended) setTimeout(poll, 3000); });
}

if (!ended) poll();
</script>
</body>
</html>
