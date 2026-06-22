@extends('layouts.admin')
@section('title', 'Admin Login - Carian Jodoh')
@section('content')

<div class="admin-card" style="max-width:380px; margin: 40px auto;">
    <h2 style="margin-bottom:18px;">🔐 Admin Login</h2>

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required autofocus>
        </div>
        <div class="field">
            <label>Kata Laluan</label>
            <input type="password" name="password" required>
        </div>
        <button class="btn" type="submit">Log Masuk</button>
    </form>
</div>

@endsection
