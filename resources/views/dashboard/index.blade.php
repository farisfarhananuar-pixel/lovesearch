@extends('layouts.app')
@section('title', 'Utama - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:14px; padding-bottom:4px;">
    <h1 style="font-size:21px;">Hai, {{ explode(' ', $user->displayName())[0] }} 👋</h1>
    <p>Tekan butang di bawah untuk dipadankan secara rawak dengan seseorang.</p>
</div>

@if ($pendingRequestsCount > 0)
    <a href="{{ route('friends.index') }}" class="friend-item in-lilac" style="margin-top:6px;">
        <div class="avatar size-sm" style="background:var(--grad-plum); color:#fff;">🔔</div>
        <div class="meta">
            <div class="name">{{ $pendingRequestsCount }} permintaan kawan menunggu</div>
            <div class="preview">Tekan untuk lihat & terima</div>
        </div>
        <div class="chev" style="color:var(--plum);">›</div>
    </a>
@endif

<details class="pref-panel" {{ session('no_filter_match') ? 'open' : '' }}>
    <summary>🎯 Keutamaan padanan (optional)</summary>
    <div class="pref-body">
        <p style="font-size:12px; color:var(--text-soft); margin:0 0 10px;">Tetapkan julat umur/semester yang anda mahu dipadankan. Boleh tinggalkan kosong kalau tak kisah.</p>
        <form method="POST" action="{{ route('match.search') }}" id="search-form">
            @csrf
            <input type="hidden" name="ignore_pref" id="ignore-pref-input" value="0">
            <input type="hidden" name="confirmed_wait" id="confirmed-wait-input" value="0">
            <div class="field-row">
                <div class="field">
                    <label>Umur dari</label>
                    <input type="number" name="pref_min_age" min="18" max="100" placeholder="18" value="{{ old('pref_min_age') }}">
                </div>
                <div class="field">
                    <label>Umur hingga</label>
                    <input type="number" name="pref_max_age" min="18" max="100" placeholder="30" value="{{ old('pref_max_age') }}">
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label>Semester dari</label>
                    <input type="number" name="pref_min_semester" min="1" max="20" placeholder="1" value="{{ old('pref_min_semester') }}">
                </div>
                <div class="field">
                    <label>Semester hingga</label>
                    <input type="number" name="pref_max_semester" min="1" max="20" placeholder="8" value="{{ old('pref_max_semester') }}">
                </div>
            </div>
        </form>
    </div>
</details>

@if (session('no_filter_match'))
<div id="no-filter-modal" class="modal-overlay">
    <div class="modal-box">
        <div style="font-size:34px; margin-bottom:6px;">😕</div>
        <h3 style="margin:0 0 8px;">Tiada calon sepadan</h3>
        <p style="font-size:13px; color:var(--text-soft); margin:0 0 16px;">
            Buat masa ini tiada sesiapa yang sepadan dengan keutamaan (umur/semester) yang anda set.
            Nak teruskan cari secara <b>rawak</b> tanpa ikut keutamaan tu, atau tunggu dalam senarai
            sehingga ada calon yang sepadan?
        </p>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <button type="button" id="btn-random-anyway" class="big-search-btn" style="width:100%;">
                💘 Teruskan rawak (tanpa filter)
            </button>
            <button type="button" id="btn-wait-filter" class="btn-secondary" style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--border); background:transparent;">
                Tunggu ikut keutamaan saya
            </button>
        </div>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,.45);
        display: flex; align-items: center; justify-content: center;
        padding: 20px; z-index: 999;
    }
    .modal-box {
        background: var(--card-bg, #fff); border-radius: 16px;
        padding: 22px; max-width: 360px; width: 100%; text-align: center;
    }
</style>

<script>
    (function () {
        var modal = document.getElementById('no-filter-modal');
        var form = document.getElementById('search-form');
        var ignoreInput = document.getElementById('ignore-pref-input');

        document.getElementById('btn-random-anyway').addEventListener('click', function () {
            ignoreInput.value = '1';
            form.submit();
        });

        document.getElementById('btn-wait-filter').addEventListener('click', function () {
            // Hantar semula form ikut keutamaan asal, terus masuk queue (skip semakan "ada calon ke tak"
            // sebab user dah confirm dia okay tunggu).
            document.getElementById('confirmed-wait-input').value = '1';
            form.submit();
        });
    })();
</script>
@endif


<div class="search-btn-wrap">
    <button class="big-search-btn" type="submit" form="search-form" {{ $user->credits < 1 ? 'disabled' : '' }}>
        <span class="icon">💘</span>
        Cari Jodoh
    </button>
</div>

@if ($user->credits < 1)
    <p style="text-align:center; font-size:13px; color:var(--text-soft); margin-top:14px;">
        Credit dah habis. <a href="{{ route('payment.index') }}"><b>Beli credit</b></a> untuk teruskan carian.
    </p>
@endif

<div class="card" style="margin-top:24px;">
    <div class="section-title">
        Senarai Kawan
        @if ($friendPreview->isNotEmpty())
            <a href="{{ route('friends.index') }}" class="see-all">Lihat semua ›</a>
        @endif
    </div>

    @if ($friendPreview->isEmpty())
        <div style="font-size:13.5px; color:var(--text-soft);">
            Belum ada kawan lagi. Bila dua-dua tekan ❤️ Suka semasa sembang, kalian akan jadi kawan kekal di sini -
            atau <a href="{{ route('friends.search') }}"><b>cari kawan ikut kod</b></a>.
        </div>
    @else
        @foreach ($friendPreview as $f)
            <a href="{{ route('match.show', $f['match_id']) }}" class="friend-item">
                <div class="avatar size-sm ring" @if($f['partner']->profile_photo) style="background-image:url('{{ $f['partner']->profile_photo }}')" @endif>
                    @if (!$f['partner']->profile_photo) {{ $f['partner']->avatarFallback() }} @endif
                </div>
                <div class="meta">
                    <div class="name">
                        {{ $f['partner']->displayName() }}
                        @if ($f['blocked']) <span class="tag blocked">Disekat</span> @endif
                    </div>
                    <div class="preview">{{ $f['last_message']->body ?? 'Mula sembang dengan kawan baru anda!' }}</div>
                </div>
                <div class="chev">›</div>
            </a>
        @endforeach
    @endif
</div>

<div class="card">
    <div class="section-title">Cara ia berfungsi</div>
    <div class="steps-list">
        <div class="step"><div class="num">1</div><p>Tekan "Cari Jodoh" - sistem padankan anda secara rawak (jantina lawan, bangsa sama, ikut keutamaan kalau ada).</p></div>
        <div class="step"><div class="num">2</div><p>Anda sembang sebagai "Misteri" - identiti dirahsiakan, ada masa 2 minit.</p></div>
        <div class="step"><div class="num">3</div><p>Kalau dua-dua tekan ❤️ Suka, nama & gambar profil didedahkan dan kalian jadi <b>kawan kekal</b> - boleh sembang tanpa had bila-bila masa.</p></div>
        <div class="step"><div class="num">4</div><p>Setiap carian guna 1 credit. Dapat 5 percuma tiap bulan.</p></div>
    </div>
</div>

<div class="card">
    <div class="section-title">Sejarah Sembang Misteri</div>
    @if ($matchHistory->isEmpty())
        <div style="font-size:13.5px; color:var(--text-soft);">Belum ada sejarah sembang lagi.</div>
    @else
        <div style="display:flex; flex-direction:column; gap:8px;">
            @foreach ($matchHistory as $h)
                <a href="{{ route('match.show', $h['id']) }}" class="friend-item">
                    <div class="avatar size-sm">{{ $h['revealed'] ? '😍' : '❓' }}</div>
                    <div class="meta">
                        <div class="name">{{ $h['name'] }}</div>
                        <div class="preview">Sembang tamat • {{ $h['ended_at']?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="chev">›</div>
                </a>
            @endforeach
        </div>
    @endif
</div>

@endsection
