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

<details class="pref-panel">
    <summary>🎯 Keutamaan padanan (optional)</summary>
    <div class="pref-body">
        <p style="font-size:12px; color:var(--text-soft); margin:0 0 10px;">Tetapkan julat umur/semester yang anda mahu dipadankan. Boleh tinggalkan kosong kalau tak kisah.</p>
        <form method="POST" action="{{ route('match.search') }}" id="search-form">
            @csrf
            <div class="field-row">
                <div class="field">
                    <label>Umur dari</label>
                    <input type="number" name="pref_min_age" min="18" max="100" placeholder="18">
                </div>
                <div class="field">
                    <label>Umur hingga</label>
                    <input type="number" name="pref_max_age" min="18" max="100" placeholder="30">
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label>Semester dari</label>
                    <input type="number" name="pref_min_semester" min="1" max="20" placeholder="1">
                </div>
                <div class="field">
                    <label>Semester hingga</label>
                    <input type="number" name="pref_max_semester" min="1" max="20" placeholder="8">
                </div>
            </div>
        </form>
    </div>
</details>

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
