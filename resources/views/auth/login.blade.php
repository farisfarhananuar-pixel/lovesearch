@extends('layouts.app')
@section('title', 'Log Masuk - Carian Jodoh')
@section('content')

<div class="hero">
    <div class="heart">💞</div>
    <h1>Jodoh Awak Mungkin Sedang Online Sekarang</h1>
    <p>Setiap padanan adalah misteri, sehinggalah hati kalian berdua kata "ya".<br>Log masuk dan mulakan carian.</p>
</div>

<div class="card">
    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label>Nombor Telefon</label>
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="0123456789" required autofocus>
        </div>
        <div class="field">
            <label>Kata Laluan</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button class="btn" type="submit">Log Masuk 💕</button>
    </form>

    <div class="link-row">
        Belum ada akaun? <a href="{{ route('register') }}"><b>Daftar sekarang</b></a>
    </div>

    <div class="link-row" style="margin-top:10px;">
        <a href="{{ route('admin.login') }}" style="display:inline-block; padding:8px 16px; border-radius:8px; border:1px solid var(--pink-dark); color:var(--pink-dark); font-weight:700; text-decoration:none;">
            🔐 Log Masuk sebagai Admin
        </a>
    </div>
</div>

<p style="text-align:center; font-size:12px; color:var(--text-soft); margin-top:10px;">
    ⚠️ Kata laluan tidak boleh ditukar/reset selepas daftar. Pastikan anda ingat ya!
</p>

@endsection
