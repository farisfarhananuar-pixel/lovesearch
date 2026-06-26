@extends('layouts.app')
@section('title', 'Game - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:14px;">
    <h1 style="font-size:21px;">🎮 Game</h1>
    <p>Bosan tunggu jodoh? Main-main dulu sambil tunggu notifikasi 😆</p>
</div>

<div class="card" style="margin-top:10px;">
    <a href="{{ route('game.uno.menu') }}" class="friend-item" style="text-decoration:none;">
        <div class="avatar size-sm" style="background:var(--grad-primary); color:#fff;">🎴</div>
        <div class="meta">
            <div class="name">UNO</div>
            <div class="preview">Solo lawan bot, atau ramai-ramai dengan kawan (2-6 orang)</div>
        </div>
        <div class="chev">›</div>
    </a>
</div>

<div class="card" style="margin-top:10px;">
    <a href="{{ route('game.chess.menu') }}" class="friend-item" style="text-decoration:none;">
        <div class="avatar size-sm" style="background:var(--grad-plum); color:#fff;">♟️</div>
        <div class="meta">
            <div class="name">Chess</div>
            <div class="preview">Solo lawan bot, atau jemput kawan untuk satu permainan</div>
        </div>
        <div class="chev">›</div>
    </a>
</div>

@endsection
