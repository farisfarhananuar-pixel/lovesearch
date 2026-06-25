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

<div class="card" style="margin-top:10px; opacity:.6;">
    <div class="friend-item">
        <div class="avatar size-sm" style="background:var(--grad-primary); color:#fff;">👥</div>
        <div class="meta">
            <div class="name">Main dengan Kawan (2-6 orang)</div>
            <div class="preview">Tak lama lagi — jemput dari senarai kawan untuk main bersama 🚧</div>
        </div>
    </div>
</div>

@endsection
