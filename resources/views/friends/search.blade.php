@extends('layouts.app')
@section('title', 'Cari Kawan - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:14px; padding-bottom:4px;">
    <div class="heart-wrap plum"><span class="heart">🔎</span></div>
    <h1 style="font-size:21px;">Cari Kawan</h1>
    <p>Masukkan kod kawan (Friend Code) untuk hantar permintaan kawan terus.</p>
</div>

<div class="card">
    <form method="POST" action="{{ route('friends.search.submit') }}">
        @csrf
        <div class="search-box">
            <input type="text" name="code" value="{{ $code }}" placeholder="CTH: AB1234" maxlength="10" required autofocus>
            <button type="submit">🔎</button>
        </div>
    </form>

    @if ($status === 'not_found')
        <p style="font-size:13px; color:var(--text-soft); margin-top:10px;">Tak jumpa pengguna dengan kod tersebut. Pastikan kod betul ya.</p>
    @elseif ($status)
        <div class="found-card">
            <div class="avatar size-md" @if($found->profile_photo) style="background-image:url('{{ $found->profile_photo }}')" @endif>
                @if(!$found->profile_photo){{ $found->avatarFallback() }}@endif
            </div>
            <div class="meta">
                <div class="name">{{ $found->displayName() }}</div>
                <div class="sub">Kod: {{ $found->friend_code }}</div>
            </div>
        </div>

        @if ($status === 'self')
            <p style="font-size:12.5px; color:var(--text-soft); margin-top:10px; text-align:center;">Ini kod anda sendiri 😄</p>
        @elseif ($status === 'already_connected')
            <p style="font-size:12.5px; color:var(--text-soft); margin-top:10px; text-align:center;">Anda sudah berkawan / berpadanan dengan pengguna ini.</p>
        @elseif ($status === 'pending_sent')
            <p style="font-size:12.5px; color:var(--text-soft); margin-top:10px; text-align:center;">Permintaan kawan sudah dihantar, tunggu jawapan dia ya.</p>
        @elseif ($status === 'pending_received')
            <p style="font-size:12.5px; color:var(--text-soft); margin-top:10px; text-align:center;">Pengguna ini dah hantar permintaan kepada anda - sila lihat di senarai Kawan.</p>
        @elseif ($status === 'found')
            <form method="POST" action="{{ route('friends.request.send', $found->id) }}" style="margin-top:14px;">
                @csrf
                <button class="btn plum" type="submit">➕ Hantar Permintaan Kawan</button>
            </form>
        @endif
    @endif
</div>

<div class="link-row"><a href="{{ route('friends.index') }}">‹ Balik ke Senarai Kawan</a></div>

@endsection
