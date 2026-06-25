@extends('layouts.app')
@section('title', 'Notifikasi - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:14px; padding-bottom:4px;">
    <h1 style="font-size:21px;">🔔 Notifikasi</h1>
    <p>Semua mesej & kemaskini akaun anda.</p>
</div>

@if ($notifications->isEmpty())
    <div class="empty-state" style="text-align:center; padding:40px 20px; color:var(--text-soft);">
        <div style="font-size:34px; margin-bottom:8px;">📭</div>
        Tiada notifikasi setakat ini.
    </div>
@else
    <div class="notif-page-list">
        @foreach ($notifications as $n)
            <a href="{{ $n->link ?? '#' }}" class="friend-item" style="margin-top:8px; {{ $n->isRead() ? '' : 'background: rgba(255,93,143,0.07);' }}">
                <div class="avatar size-sm" style="background:var(--grad-plum); color:#fff;">
                    @if ($n->type === 'credit') 💎
                    @elseif ($n->type === 'message') 💬
                    @elseif ($n->type === 'friend_request') 🔔
                    @else 📌
                    @endif
                </div>
                <div class="meta">
                    <div class="name">{{ $n->title }}</div>
                    @if ($n->body)
                        <div class="preview">{{ $n->body }}</div>
                    @endif
                    <div class="preview" style="font-size:11px;">{{ $n->created_at->diffForHumans() }}</div>
                </div>
                @if (!$n->isRead())
                    <div class="chev" style="color:var(--pink-dark);">●</div>
                @endif
            </a>
        @endforeach
    </div>
@endif

@endsection
