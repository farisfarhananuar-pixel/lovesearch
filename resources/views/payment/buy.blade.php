@extends('layouts.app')
@section('title', 'Beli Credit - Carian Jodoh')
@section('content')

<div class="hero">
    <div class="heart-wrap"><span class="heart">💎</span></div>
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

        @if ($qrData)
            <p style="font-size:13px; color:var(--text-soft); text-align:center; margin-bottom:6px;">
                Imbas QR di bawah untuk bayar:
            </p>
            <div class="qr-box">
                <img src="{{ $qrData }}" alt="QR Code Bayaran">
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
            <input type="file" name="receipt" id="receiptInput" accept="image/*" required>
            <p id="receiptHint" style="font-size:11px; color:var(--text-soft); margin-top:4px;">Boleh ambil terus dengan kamera atau pilih dari galeri.</p>
        </div>

        <button class="btn" type="submit" id="paymentSubmitBtn">Hantar Resit</button>
    </form>

    <p style="font-size:12px; color:var(--text-soft); text-align:center; margin-top:10px;">
        Selepas dihantar, admin akan semak dan credit akan masuk automatik bila diluluskan.
    </p>
</div>

<script>
(function () {
    // Gambar dari kamera phone (terutamanya iPhone - format HEIC, atau Android
    // resolusi tinggi) selalunya jauh lebih besar (6-15MB+) berbanding screenshot
    // desktop yang biasa digunakan untuk test. Saiz besar ni yang menyebabkan
    // upload resit "gagal" kat phone (validation server tolak fail >5MB, atau
    // format HEIC tak dikenali sebagai 'image').
    //
    // Fix: mampat & tukar terus jadi JPEG dalam browser (guna <canvas>) sebelum
    // dihantar - saiz fail jadi kecil & format sentiasa serasi, tak kira jenis
    // phone/kamera. Kalau browser tak menyokong (jarang), biar fail asal
    // dihantar macam biasa supaya tak block submission terus.
    var form = document.getElementById('payment-form');
    var fileInput = document.getElementById('receiptInput');
    var hint = document.getElementById('receiptHint');
    var submitBtn = document.getElementById('paymentSubmitBtn');

    if (!form || !fileInput || !window.FileReader || !window.HTMLCanvasElement) return;

    var originalBtnText = submitBtn.textContent;
    var isCompressing = false;

    function compressImage(file, maxDim, quality) {
        return new Promise(function (resolve, reject) {
            var img = new Image();
            var url = URL.createObjectURL(file);

            img.onload = function () {
                URL.revokeObjectURL(url);

                var width = img.naturalWidth;
                var height = img.naturalHeight;

                if (width > maxDim || height > maxDim) {
                    var ratio = Math.min(maxDim / width, maxDim / height);
                    width = Math.round(width * ratio);
                    height = Math.round(height * ratio);
                }

                var canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(img, 0, 0, width, height);

                canvas.toBlob(function (blob) {
                    if (blob) resolve(blob); else reject(new Error('toBlob gagal'));
                }, 'image/jpeg', quality);
            };

            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Gagal baca gambar'));
            };

            img.src = url;
        });
    }

    fileInput.addEventListener('change', function () {
        var file = fileInput.files[0];
        if (!file) return;

        // Fail dah kecil (cth: screenshot) - tak payah mampat lagi, jimat masa.
        if (file.size <= 800 * 1024) {
            hint.textContent = 'Gambar sedia untuk dihantar ✓';
            return;
        }

        isCompressing = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Memampatkan gambar...';
        hint.textContent = 'Sedang memampatkan gambar, sila tunggu sekejap...';

        compressImage(file, 1600, 0.75).then(function (blob) {
            var compressed = new File([blob], 'resit.jpg', { type: 'image/jpeg' });
            var dt = new DataTransfer();
            dt.items.add(compressed);
            fileInput.files = dt.files;
            hint.textContent = 'Gambar sedia untuk dihantar ✓ (' + (compressed.size / 1024).toFixed(0) + ' KB)';
        }).catch(function () {
            hint.textContent = 'Boleh ambil terus dengan kamera atau pilih dari galeri.';
        }).finally(function () {
            isCompressing = false;
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        });
    });

    form.addEventListener('submit', function (e) {
        if (isCompressing) e.preventDefault();
    });
})();
</script>

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
