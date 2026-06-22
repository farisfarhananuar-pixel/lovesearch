@extends('layouts.admin')
@section('title', 'Settings - Admin')
@section('content')

<div class="admin-nav">
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a href="{{ route('admin.payments') }}">Pembayaran</a>
    <a href="{{ route('admin.settings') }}" class="active">Settings</a>
</div>

<div class="admin-card">
    <h3 style="margin-bottom:10px;">📷 QR Code Pembayaran</h3>
    <p style="font-size:13px;color:#777;margin-bottom:12px;">
        Upload QR code DuitNow/bank anda di sini. Ini akan dipaparkan kepada user semasa mereka nak beli credit.
    </p>

    @if ($qrPath)
        <div class="qr-box">
            <img src="{{ asset('storage/' . $qrPath) }}" alt="QR Code Semasa">
        </div>
    @else
        <p style="font-size:13px;color:#c4304a;margin-bottom:12px;">⚠️ Belum ada QR code diupload lagi.</p>
    @endif

    <form method="POST" action="{{ route('admin.settings.qr') }}" enctype="multipart/form-data">
        @csrf
        <div class="field">
            <label>Upload QR Code Baru</label>
            <input type="file" name="qr_code" accept="image/*" required>
        </div>
        <button class="btn" type="submit">Kemaskini QR Code</button>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-bottom:10px;">🔑 Tukar Password Admin</h3>

    <form method="POST" action="{{ route('admin.settings.password') }}">
        @csrf
        <div class="field">
            <label>Password Semasa</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="field">
            <label>Password Baru</label>
            <input type="password" name="new_password" required minlength="6">
        </div>
        <div class="field">
            <label>Sahkan Password Baru</label>
            <input type="password" name="new_password_confirmation" required minlength="6">
        </div>
        <button class="btn" type="submit">Tukar Password</button>
    </form>
</div>

@endsection
