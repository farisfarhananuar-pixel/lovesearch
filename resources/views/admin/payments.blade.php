@extends('layouts.admin')
@section('title', 'Pembayaran - Admin')
@section('content')

<div class="admin-nav">
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a href="{{ route('admin.payments') }}" class="active">Pembayaran</a>
    <a href="{{ route('admin.settings') }}">Settings</a>
</div>

<div class="admin-card">
    <h3 style="margin-bottom:6px;">⏳ Menunggu Kelulusan ({{ $pending->count() }})</h3>

    @forelse ($pending as $payment)
        <div class="payment-row">
            <a href="{{ $payment->receiptSrc() }}" target="_blank">
                <img class="receipt-thumb" src="{{ $payment->receiptSrc() }}" alt="Resit">
            </a>
            <div class="info">
                <b>{{ $payment->payer_full_name }}</b><br>
                User: {{ $payment->user->full_name }} ({{ $payment->user->phone }})<br>
                {{ $payment->package_credits }} credit &middot; RM{{ number_format($payment->package_price, 2) }}<br>
                <span style="color:#999;">{{ $payment->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="actions">
                <form method="POST" action="{{ route('admin.payments.approve', $payment) }}" onsubmit="return confirm('Lulus pembayaran ini?');">
                    @csrf
                    <button class="btn-approve" type="submit">Lulus</button>
                </form>
                <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" onsubmit="return confirm('Tolak pembayaran ini?');">
                    @csrf
                    <button class="btn-reject" type="submit">Tolak</button>
                </form>
            </div>
        </div>
    @empty
        <p style="color:#999; padding:14px 0;">Tiada pembayaran pending sekarang.</p>
    @endforelse
</div>

<div class="admin-card">
    <h3 style="margin-bottom:6px;">📋 Sejarah Terkini</h3>

    @forelse ($recent as $payment)
        <div class="payment-row">
            <a href="{{ $payment->receiptSrc() }}" target="_blank">
                <img class="receipt-thumb" src="{{ $payment->receiptSrc() }}" alt="Resit">
            </a>
            <div class="info">
                <b>{{ $payment->payer_full_name }}</b><br>
                User: {{ $payment->user->full_name }}<br>
                {{ $payment->package_credits }} credit &middot; RM{{ number_format($payment->package_price, 2) }}
            </div>
            <div class="actions">
                <span class="badge {{ $payment->status }}">{{ ucfirst($payment->status) }}</span>
            </div>
        </div>
    @empty
        <p style="color:#999; padding:14px 0;">Belum ada sejarah pembayaran.</p>
    @endforelse
</div>

@endsection
