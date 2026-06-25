@extends('layouts.app')
@section('title', 'Profil - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:14px; padding-bottom:4px;">
    <h1 style="font-size:21px;">Profil Saya</h1>
    <p>Tukar gambar profil & nama paparan anda.</p>
</div>

<div class="card">
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="profile-form">
        @csrf

        <div class="profile-photo-wrap">
            <div class="profile-photo-edit">
                <div class="avatar ring" id="photo-preview" @if($user->profile_photo) style="background-image:url('{{ $user->profile_photo }}')" @endif>
                    @if (!$user->profile_photo) {{ $user->avatarFallback() }} @endif
                </div>
                <label class="cam-btn" for="photo-input">📷</label>
                <input type="file" id="photo-input" name="profile_photo" accept="image/*" onchange="previewPhoto(this)">
            </div>
            <div class="profile-code-label">Kod Kawan anda: <b>{{ $user->friend_code }}</b></div>
            @error('profile_photo') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label>Nama Paparan</label>
            <input type="text" name="display_name" value="{{ old('display_name', $user->display_name) }}" placeholder="{{ $user->full_name }}" maxlength="50">
            <div class="hint">Nama ini akan dipaparkan kepada kawan/padanan anda. Kosongkan untuk guna nama penuh ({{ $user->full_name }}).</div>
            @error('display_name') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <button class="btn" type="submit">Simpan Perubahan</button>
    </form>

    @if ($user->profile_photo)
        <form method="POST" action="{{ route('profile.photo.remove') }}" style="margin-top:10px;" onsubmit="return confirm('Buang gambar profil?');">
            @csrf
            <button class="btn danger" type="submit">🗑️ Buang Gambar Profil</button>
        </form>
    @endif
</div>

<div class="card">
    <div class="section-title">Maklumat Akaun</div>
    <div class="steps-list">
        <div class="step"><p><b>Nama Penuh:</b> {{ $user->full_name }}</p></div>
        <div class="step"><p><b>Nombor Telefon:</b> {{ $user->phone }}</p></div>
        <div class="step"><p><b>Umur:</b> {{ $user->age ?? '-' }} tahun</p></div>
        <div class="step"><p><b>Semester:</b> {{ $user->semester ?? '-' }}</p></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const el = document.getElementById('photo-preview');
            el.style.backgroundImage = `url('${e.target.result}')`;
            el.innerText = '';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
