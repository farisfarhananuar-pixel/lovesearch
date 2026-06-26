@extends('layouts.app')
@section('title', 'Chess - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:14px; padding-bottom:4px;">
    <h1 style="font-size:21px;">♟️ Chess</h1>
    <p>Main solo lawan bot, atau jemput kawan untuk satu permainan santai.</p>
</div>

<div class="card">
    <form method="POST" action="{{ route('game.chess.solo') }}">
        @csrf
        <button type="submit" class="friend-item" style="width:100%; text-align:left; border:none; background:none; cursor:pointer; font:inherit;">
            <div class="avatar size-sm" style="background:var(--grad-plum); color:#fff;">🤖</div>
            <div class="meta">
                <div class="name">Main Solo (lawan Bot)</div>
                <div class="preview">Seorang je? Boleh main terus lawan bot, sedia main!</div>
            </div>
            <div class="chev">›</div>
        </button>
    </form>
</div>

@if ($activeRooms->isNotEmpty())
<div class="card">
    <div class="section-title">Permainan Aktif ({{ $activeRooms->count() }})</div>
    @foreach ($activeRooms as $r)
        @php $opp = $r->players->first(fn($p) => $p->user_id !== auth()->id()); @endphp
        <a href="{{ route('game.chess.show', $r->id) }}" class="friend-item in-lilac">
            <div class="avatar size-sm" @if($opp->is_bot) style="background:var(--grad-plum); color:#fff;" @elseif($opp->user->profile_photo) style="background-image:url('{{ $opp->user->profile_photo }}')" @endif>
                @if ($opp->is_bot) 🤖 @elseif(!$opp->user->profile_photo) {{ $opp->user->avatarFallback() }} @endif
            </div>
            <div class="meta">
                <div class="name">{{ $opp->is_bot ? 'Bot' : $opp->user->displayName() }}</div>
                <div class="preview">Sambung permainan yang sedang berjalan</div>
            </div>
            <div class="chev">›</div>
        </a>
    @endforeach
</div>
@endif

@if ($pendingReceived->isNotEmpty())
<div class="card">
    <div class="section-title">Jemputan Diterima ({{ $pendingReceived->count() }})</div>
    @foreach ($pendingReceived as $invite)
        @php $creator = $invite->room->creator; @endphp
        <div class="request-row">
            <div class="avatar size-sm" @if($creator->profile_photo) style="background-image:url('{{ $creator->profile_photo }}')" @endif>
                @if(!$creator->profile_photo){{ $creator->avatarFallback() }}@endif
            </div>
            <div class="meta">{{ $creator->displayName() }} menjemput anda</div>
            <div class="actions">
                <form method="POST" action="{{ route('game.chess.accept', $invite->game_room_id) }}">
                    @csrf
                    <button class="btn-accept" type="submit">Terima</button>
                </form>
                <form method="POST" action="{{ route('game.chess.decline', $invite->game_room_id) }}">
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
    <div class="section-title">Jemputan Dihantar ({{ $pendingSent->count() }})</div>
    @foreach ($pendingSent as $r)
        @php $invitee = $r->players->first(fn($p) => $p->user_id !== auth()->id())->user; @endphp
        <div class="request-row">
            <div class="avatar size-sm" @if($invitee->profile_photo) style="background-image:url('{{ $invitee->profile_photo }}')" @endif>
                @if(!$invitee->profile_photo){{ $invitee->avatarFallback() }}@endif
            </div>
            <div class="meta">{{ $invitee->displayName() }} <span style="color:var(--text-faint); font-weight:600;">· menunggu</span></div>
        </div>
    @endforeach
</div>
@endif

<div class="card">
    <div class="section-title">Jemput Kawan Main ({{ $friends->count() }})</div>

    @if ($friends->isEmpty())
        <div class="empty-state">
            <p>Belum ada kawan lagi.<br>Tambah kawan dulu untuk boleh jemput main chess.</p>
        </div>
    @else
        @foreach ($friends as $f)
            <div class="friend-item">
                <div class="avatar size-sm" @if($f->profile_photo) style="background-image:url('{{ $f->profile_photo }}')" @endif>
                    @if (!$f->profile_photo) {{ $f->avatarFallback() }} @endif
                </div>
                <div class="meta">
                    <div class="name">{{ $f->displayName() }}</div>
                </div>
                <form method="POST" action="{{ route('game.chess.invite', $f->id) }}">
                    @csrf
                    <button type="submit" class="btn-accept">Jemput</button>
                </form>
            </div>
        @endforeach
    @endif
</div>

@endsection
