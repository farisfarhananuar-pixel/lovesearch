<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Sembang Misteri - Carian Jodoh</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="chat-header">
    <a href="{{ route('dashboard') }}" class="chat-home-btn" title="Kembali ke Utama">🏠</a>
    <div class="chat-avatar" id="avatarBox">{{ $revealed ? '😍' : '❓' }}</div>
    <div class="who">
        <div class="name" id="partnerName">{{ $revealed ? $partner->full_name : 'Misteri' }}</div>
        <div class="sub" id="partnerSub">{{ ucfirst($partner->gender) }} • {{ ucfirst($partner->race) }}</div>
    </div>
    @if (!$revealed)
        <div class="chat-timer" id="timerBox">02:00</div>
    @else
        <div class="chat-timer" style="background:#e1f8e9; color:#1d9e57;">Tanpa Had ⏳</div>
    @endif
</div>

@if (!$revealed && $match->status !== 'ended')
<div class="love-banner" id="loveBanner">
    💡 Identiti dirahsiakan. Tekan ❤️ kalau anda suka - kalau kedua-dua tekan, nama akan terdedah!
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

<div class="chat-footer" id="chatFooter" style="{{ $match->status === 'ended' ? 'display:none' : '' }}">
    <button class="love {{ $match->lovedBy($user) ? 'active' : '' }}" id="loveBtn" title="Suka">❤️</button>
    <input type="text" id="msgInput" placeholder="Taip mesej..." maxlength="500" autocomplete="off">
    <button class="send" id="sendBtn">➤</button>
</div>

<script>
const matchId = {{ $match->id }};
const pollUrl = '{{ route("match.poll", $match->id) }}';
const sendUrl = '{{ route("match.message", $match->id) }}';
const loveUrl = '{{ route("match.love", $match->id) }}';
const csrfToken = '{{ csrf_token() }}';

let lastId = {{ $messages->max('id') ?? 0 }};
let revealed = {{ $revealed ? 'true' : 'false' }};
let ended = {{ $match->status === 'ended' ? 'true' : 'false' }};

const chatBody = document.getElementById('chatBody');
const timerBox = document.getElementById('timerBox');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const loveBtn = document.getElementById('loveBtn');
const endedCard = document.getElementById('endedCard');
const chatFooter = document.getElementById('chatFooter');
const loveBanner = document.getElementById('loveBanner');

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

function applyReveal(partnerName) {
    revealed = true;
    document.getElementById('avatarBox').textContent = '😍';
    document.getElementById('partnerName').textContent = partnerName;
    if (timerBox) {
        timerBox.textContent = 'Tanpa Had ⏳';
        timerBox.style.background = '#e1f8e9';
        timerBox.style.color = '#1d9e57';
    }
    if (loveBanner) loveBanner.style.display = 'none';
}

function sendMessage() {
    const body = msgInput.value.trim();
    if (!body || ended) return;
    msgInput.value = '';
    fetch(sendUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ body })
    }).then(r => r.json()).then(data => {
        if (data.error) { showEnded(); }
    });
}

sendBtn.addEventListener('click', sendMessage);
msgInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });

loveBtn.addEventListener('click', () => {
    if (ended) return;
    fetch(loveUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => {
        loveBtn.classList.add('active');
    });
});

function poll() {
    fetch(pollUrl + '?after_id=' + lastId, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            data.messages.forEach(m => {
                appendMessage(m);
                lastId = m.id;
            });

            if (data.revealed && !revealed) {
                applyReveal(data.partner_name);
            }
            if (data.partner_loved) {
                document.getElementById('partnerSub').textContent = 'Dia dah tekan ❤️ juga!';
            }
            if (!revealed && data.seconds_left !== null && timerBox) {
                const m = Math.floor(data.seconds_left / 60);
                const s = data.seconds_left % 60;
                timerBox.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                if (data.seconds_left <= 20) timerBox.classList.add('urgent');
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
