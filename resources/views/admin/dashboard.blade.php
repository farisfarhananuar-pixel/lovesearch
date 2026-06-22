@extends('layouts.admin')
@section('title', 'Dashboard Admin - Carian Jodoh')
@section('content')

<div class="admin-nav">
    <a href="{{ route('admin.dashboard') }}" class="active">Dashboard</a>
    <a href="{{ route('admin.payments') }}">Pembayaran</a>
    <a href="{{ route('admin.settings') }}">Settings</a>
</div>

<div class="admin-stats">
    <div class="admin-stat">
        <div class="num">{{ $stats['total_users'] }}</div>
        <div class="label">Jumlah User</div>
    </div>
    <div class="admin-stat">
        <div class="num">{{ $stats['pending_payments'] }}</div>
        <div class="label">Pembayaran Pending</div>
    </div>
    <div class="admin-stat">
        <div class="num">{{ $stats['approved_payments'] }}</div>
        <div class="label">Pembayaran Diluluskan</div>
    </div>
    <div class="admin-stat">
        <div class="num">{{ $stats['total_lelaki'] }} / {{ $stats['total_perempuan'] }}</div>
        <div class="label">Lelaki / Perempuan</div>
    </div>
</div>

@if ($stats['pending_payments'] > 0)
    <div class="admin-card">
        <p style="margin-bottom:10px;">⚠️ Ada <b>{{ $stats['pending_payments'] }}</b> pembayaran yang menunggu kelulusan anda.</p>
        <a href="{{ route('admin.payments') }}" class="btn" style="display:inline-block;width:auto;padding:10px 20px;text-decoration:none;">Semak Sekarang</a>
    </div>
@endif

@endsection
