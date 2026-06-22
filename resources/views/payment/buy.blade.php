@extends('layouts.app')
@section('title', 'Beli Credit - Carian Jodoh')
@section('content')

<div class="hero">
    <div class="heart">💎</div>
    <h1>Tambah Credit</h1>
    <p>Credit percuma dah habis? Tambah sikit untuk terus cari jodoh anda.</p>
</div>

<div class="card">
    <div style="margin-bottom:16px; text-align:center;">
        <span class="credit-badge">💰 Baki Sekarang: {{ auth()->user()->credits }} credit</span>
    </div>

    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('payment.submit') }}" enctype="multipart/form-data" id="payment-form">
        @csrf

        <div class="field">
            <label>Pilih Pakej</label>
            <div class="choice-group">
                @foreach ($packages as $key => $pkg)
                    <label>
                        <input type="radio" name="package" value="{{ $key }}" {{ old('package') == $key ? 'checked' : '' }} required>
                        <span>{{ $pkg['credits'] }} kali main<br>RM{{ number_format($pkg['price'], 2) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        @if ($qrPath)
            <p style="font-size:13px; color:var(--text-soft); text-align:center; margin-bottom:6px;">
                Imbas QR di bawah untuk bayar:
            </p>
            <div class="qr-box">
                <img src="{{ asset('storage/' . $qrPath) }}" alt="QR Code Bayaran">
            </div>
        @else
            <p style="font-size:13px; color:#c4304a; text-align:center; margin-bottom:14px;">
                ⚠️ QR code bayaran belum disediakan. Sila hubungi admin.
            </p>
        @endif

        <div class="field">
            <label>Nama Penuh Pembayar (ikut akaun bank)</label>
            <input type="text" name="payer_full_name" value="{{ old('payer_full_name') }}" placeholder="Cth: Ahmad Bin Ali" required>
        </div>

        <div class="field">
            <label>Muat Naik Resit Pembayaran</label>
            <input type="file" name="receipt" accept="image/*" required>
        </div>

        <button class="btn" type="submit">Hantar Resit</button>
    </form>

    <p style="font-size:12px; color:var(--text-soft); text-align:center; margin-top:10px;">
        Selepas dihantar, admin akan semak dan credit akan masuk automatik bila diluluskan.
    </p>
</div>

@if ($recentPayments->count())
    <div class="card">
        <h3 style="margin-bottom:10px;">Pembayaran Terkini</h3>
        @foreach ($recentPayments as $p)
            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #eee; font-size:13.5px;">
                <span>{{ $p->package_credits }} credit &middot; RM{{ number_format($p->package_price, 2) }}</span>
                <span class="badge {{ $p->status }}">{{ ucfirst($p->status) }}</span>
            </div>
        @endforeach
        <div class="link-row" style="margin-top:10px;">
            <a href="{{ route('payment.history') }}">Lihat semua sejarah →</a>
        </div>
    </div>
@endif

@endsection
