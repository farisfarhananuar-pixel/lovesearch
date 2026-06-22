@extends('layouts.app')
@section('title', 'Utama - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:18px; padding-bottom:6px;">
    <h1 style="font-size:21px;">Hai, {{ explode(' ', $user->full_name)[0] }} 👋</h1>
    <p>Tekan butang di bawah untuk dipadankan secara rawak dengan seseorang.</p>
</div>

<div class="stat-row">
    <div class="credit-badge">💎 {{ $user->credits }} carian baki bulan ini</div>
</div>

<div class="search-btn-wrap">
    <form method="POST" action="{{ route('match.search') }}">
        @csrf
        <button class="big-search-btn" type="submit" {{ $user->credits < 1 ? 'disabled' : '' }}>
            <span class="icon">💘</span>
            Cari Jodoh
        </button>
    </form>
</div>

@if ($user->credits < 1)
    <p style="text-align:center; font-size:13px; color:var(--text-soft); margin-top:14px;">
        Credit dah habis. <a href="{{ route('payment.index') }}"><b>Beli credit</b></a> untuk teruskan carian.
    </p>
@endif

<div class="card" style="margin-top:24px;">
    <div style="font-weight:700; margin-bottom:10px; color:var(--pink-dark);">Cara ia berfungsi</div>
    <div style="font-size:13.5px; color:var(--text-soft); line-height:1.7;">
        1️⃣ Tekan "Cari Jodoh" - sistem akan padankan anda secara rawak (jantina lawan, bangsa sama).<br>
        2️⃣ Anda akan sembang sebagai "Misteri" - identiti dirahsiakan, ada masa 2 minit.<br>
        3️⃣ Kalau dua-dua tekan ❤️ Suka, nama sebenar akan didedahkan dan sembang jadi tanpa had masa.<br>
        4️⃣ Setiap carian guna 1 credit. Dapat 5 percuma tiap bulan.
    </div>
</div>

@endsection
