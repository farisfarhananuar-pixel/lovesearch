@extends('layouts.app')
@section('title', 'Sejarah Pembayaran - Carian Jodoh')
@section('content')

<div class="hero">
    <div class="heart-wrap"><span class="heart">📋</span></div>
    <h1>Sejarah Pembayaran</h1>
    <p>Senarai semua transaksi credit anda.</p>
</div>

<div class="card">
    @forelse ($payments as $p)
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #eee;">
            <div>
                <div style="font-weight:700; font-size:14.5px;">{{ $p->package_credits }} credit</div>
                <div style="font-size:13px; color:var(--text-soft);">
                    RM{{ number_format($p->package_price, 2) }} &middot;
                    Atas nama: {{ $p->payer_full_name }}
                </div>
                <div style="font-size:12px; color:#aaa; margin-top:2px;">
                    {{ $p->created_at->format('d M Y, H:i') }}
                </div>
            </div>
            <span class="badge {{ $p->status }}">
                @if ($p->status === 'pending') ⏳ Pending
                @elseif ($p->status === 'approved') ✅ Diluluskan
                @else ❌ Ditolak
                @endif
            </span>
        </div>
    @empty
        <p style="text-align:center; color:var(--text-soft); padding:20px 0;">
            Belum ada sejarah pembayaran.
        </p>
    @endforelse
</div>

<div style="text-align:center; margin-top:6px;">
    <a href="{{ route('payment.index') }}" class="btn" style="display:inline-block; width:auto; padding:12px 28px; text-decoration:none;">+ Beli Credit</a>
</div>

@endsection
