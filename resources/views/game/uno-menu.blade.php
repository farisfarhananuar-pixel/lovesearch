@extends('layouts.app')
@section('title', 'UNO - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:14px;">
    <h1 style="font-size:21px;">🎴 UNO</h1>
    <p>Main solo lawan bot, atau jemput kawan untuk main bersama.</p>
</div>

<div class="card" style="margin-top:10px;">
    <a href="{{ route('game.uno.solo') }}" class="friend-item" style="text-decoration:none;">
        <div class="avatar size-sm" style="background:var(--grad-plum); color:#fff;">🤖</div>
        <div class="meta">
            <div class="name">Main Solo (lawan Bot)</div>
            <div class="preview">Seorang je? Boleh main terus lawan bot, sedia main!</div>
        </div>
        <div class="chev">›</div>
    </a>
</div>

@if ($myRooms->isNotEmpty())
<div class="card">
    <div class="section-title">Bilik UNO Anda ({{ $myRooms->count() }})</div>
    @foreach ($myRooms as $r)
        @php
            $joinedCount = $r->players->where('status', 'joined')->count();
        @endphp
        <a href="{{ route('game.uno.room', $r->id) }}" class="friend-item in-lilac">
            <div class="avatar size-sm" style="background:var(--grad-primary); color:#fff;">🎴</div>
            <div class="meta">
                <div class="name">{{ $r->status === 'waiting' ? 'Menunggu pemain...' : 'Sedang bermain' }}</div>
                <div class="preview">{{ $joinedCount }} orang sudah sertai</div>
            </div>
            <div class="chev">›</div>
        </a>
    @endforeach
</div>
@endif

@if ($pendingReceived->isNotEmpty())
<div class="card">
    <div class="section-title">Jemputan UNO ({{ $pendingReceived->count() }})</div>
    @foreach ($pendingReceived as $invite)
        @php $creator = $invite->room->creator; @endphp
        <div class="request-row">
            <div class="avatar size-sm" @if($creator->profile_photo) style="background-image:url('{{ $creator->profile_photo }}')" @endif>
                @if(!$creator->profile_photo){{ $creator->avatarFallback() }}@endif
            </div>
            <div class="meta">{{ $creator->displayName() }} menjemput anda</div>
            <div class="actions">
                <form method="POST" action="{{ route('game.uno.join', $invite->game_room_id) }}">
                    @csrf
                    <button class="btn-accept" type="submit">Sertai</button>
                </form>
                <form method="POST" action="{{ route('game.uno.decline', $invite->game_room_id) }}">
                    @csrf
                    <button class="btn-decline" type="submit">Tolak</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endif

<div class="card">
    <div class="section-title">Jemput Kawan Main UNO (2-6 orang)</div>

    @if ($friends->isEmpty())
        <div class="empty-state">
            <p>Belum ada kawan lagi.<br>Tambah kawan dulu untuk boleh jemput main UNO.</p>
        </div>
    @else
        <form method="POST" action="{{ route('game.uno.store') }}">
            @csrf
            @foreach ($friends as $f)
                <label class="friend-item" style="cursor:pointer;">
                    <input type="checkbox" name="friend_ids[]" value="{{ $f->id }}" style="width:18px; height:18px; flex-shrink:0;">
                    <div class="avatar size-sm" @if($f->profile_photo) style="background-image:url('{{ $f->profile_photo }}')" @endif>
                        @if (!$f->profile_photo) {{ $f->avatarFallback() }} @endif
                    </div>
                    <div class="meta">
                        <div class="name">{{ $f->displayName() }}</div>
                    </div>
                </label>
            @endforeach
            <button type="submit" class="btn" style="width:100%; margin-top:12px;">Jemput & Cipta Bilik</button>
            <p style="font-size:11px; color:var(--text-soft); text-align:center; margin-top:8px;">Pilih sehingga 5 kawan (maksimum 6 orang sebilik).</p>
        </form>
    @endif
</div>

@endsection
