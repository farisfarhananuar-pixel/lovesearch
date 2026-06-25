@extends('layouts.app')
@section('title', 'Kawan - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:14px; padding-bottom:4px;">
    <h1 style="font-size:21px;">Kawan Anda 👥</h1>
    <p>Senarai kawan yang dah sama-sama tekan ❤️ Suka, atau ditambah terus ikut kod kawan.</p>
</div>

<div class="code-chip">
    <div>
        <div class="label">Kod Kawan Anda</div>
        <div class="code" id="my-code">{{ $user->friend_code }}</div>
    </div>
    <button type="button" onclick="copyCode()">📋 Salin</button>
</div>

<a href="{{ route('friends.search') }}" class="btn plum" style="margin-bottom:18px;">🔎 Cari & Tambah Kawan</a>

@if ($pendingReceived->isNotEmpty())
<div class="card">
    <div class="section-title">Permintaan Kawan ({{ $pendingReceived->count() }})</div>
    @foreach ($pendingReceived as $r)
        <div class="request-row">
            <div class="avatar size-sm" @if($r->sender->profile_photo) style="background-image:url('{{ $r->sender->profile_photo }}')" @endif>
                @if(!$r->sender->profile_photo){{ $r->sender->avatarFallback() }}@endif
            </div>
            <div class="meta">{{ $r->sender->displayName() }}</div>
            <div class="actions">
                <form method="POST" action="{{ route('friends.request.accept', $r->id) }}">
                    @csrf
                    <button class="btn-accept" type="submit">Terima</button>
                </form>
                <form method="POST" action="{{ route('friends.request.decline', $r->id) }}">
                    @csrf
                    <button class="btn-decline" type="submit">Tolak</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endif

@if ($pendingSent->isNotEmpty())
<div class="card">
    <div class="section-title">Permintaan Dihantar ({{ $pendingSent->count() }})</div>
    @foreach ($pendingSent as $r)
        <div class="request-row">
            <div class="avatar size-sm" @if($r->receiver->profile_photo) style="background-image:url('{{ $r->receiver->profile_photo }}')" @endif>
                @if(!$r->receiver->profile_photo){{ $r->receiver->avatarFallback() }}@endif
            </div>
            <div class="meta">{{ $r->receiver->displayName() }} <span style="color:var(--text-faint); font-weight:600;">· menunggu</span></div>
            <div class="actions">
                <form method="POST" action="{{ route('friends.request.cancel', $r->id) }}">
                    @csrf
                    <button class="btn-decline" type="submit">Batal</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endif

<div class="card">
    <div class="section-title">Senarai Kawan ({{ $friends->count() }})</div>

    @if ($friends->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="60" cy="60" r="56" fill="#FCEFF5"/>
                <circle cx="44" cy="52" r="14" fill="#FFD2E1"/>
                <circle cx="78" cy="52" r="14" fill="#E6DBFB"/>
                <path d="M28 88c4-14 16-22 32-22s28 8 32 22" stroke="#E8447A" stroke-width="4" stroke-linecap="round" fill="none"/>
                <path d="M60 40l3.5 7.5L71 51l-7.5 3.5L60 62l-3.5-7.5L49 51l7.5-3.5z" fill="#FF7A59"/>
            </svg>
            <p>Belum ada kawan lagi.<br>Cari jodoh atau cari kawan ikut kod untuk mula!</p>
        </div>
    @else
        @foreach ($friends as $f)
            <a href="{{ route('match.show', $f['match_id']) }}" class="friend-item in-lilac">
                <div class="avatar size-sm ring" @if($f['partner']->profile_photo) style="background-image:url('{{ $f['partner']->profile_photo }}')" @endif>
                    @if (!$f['partner']->profile_photo) {{ $f['partner']->avatarFallback() }} @endif
                </div>
                <div class="meta">
                    <div class="name">
                        {{ $f['partner']->displayName() }}
                        @if ($f['blocked']) <span class="tag blocked">Disekat</span> @endif
                    </div>
                    <div class="preview">{{ $f['last_message']->body ?? 'Mula sembang dengan kawan baru anda!' }}</div>
                </div>
                <div class="chev">›</div>
            </a>
        @endforeach
    @endif
</div>

@endsection

@push('scripts')
<script>
function copyCode() {
    const text = document.getElementById('my-code').innerText;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    }
    const btn = event.target;
    const old = btn.innerText;
    btn.innerText = '✅ Disalin';
    setTimeout(() => btn.innerText = old, 1500);
}
</script>
@endpush
