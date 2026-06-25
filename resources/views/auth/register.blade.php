@extends('layouts.app')
@section('title', 'Daftar - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:24px;">
    <div class="heart-wrap"><span class="heart">💘</span></div>
    <h1>Mulakan Cerita Cinta Anda</h1>
    <p>Identiti anda akan dirahsiakan sehingga kalian berdua sama-sama tekan ❤️ Suka.</p>
</div>

<div class="card">
    <form method="POST" action="{{ route('register') }}" autocomplete="off">
        @csrf

        <div class="field">
            <label>Nama Penuh</label>
            <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Nama penuh anda" required>
            @error('full_name') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label>Nombor Telefon</label>
            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="0123456789" required>
            @error('phone') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label>Kata Laluan</label>
            <input type="password" name="password" placeholder="Minimum 6 aksara" required>
            <div style="font-size:11px;color:var(--text-soft);margin-top:4px;">⚠️ Tidak boleh ditukar lepas ni - sila ingat baik-baik.</div>
            @error('password') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label>Jantina</label>
            <div class="choice-group">
                <label><input type="radio" name="gender" value="lelaki" {{ old('gender') == 'lelaki' ? 'checked' : '' }} required><span>👦 Lelaki</span></label>
                <label><input type="radio" name="gender" value="perempuan" {{ old('gender') == 'perempuan' ? 'checked' : '' }}><span>👧 Perempuan</span></label>
            </div>
            @error('gender') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label>Bangsa</label>
            <div class="choice-group">
                <label><input type="radio" name="race" value="melayu" {{ old('race') == 'melayu' ? 'checked' : '' }} required><span>Melayu</span></label>
                <label><input type="radio" name="race" value="cina" {{ old('race') == 'cina' ? 'checked' : '' }}><span>Cina</span></label>
                <label><input type="radio" name="race" value="india" {{ old('race') == 'india' ? 'checked' : '' }}><span>India</span></label>
            </div>
            <div style="font-size:11px;color:var(--text-soft);margin-top:4px;">Sistem akan padankan anda dengan bangsa yang sama.</div>
            @error('race') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field-row">
            <div class="field">
                <label>Umur</label>
                <input type="number" name="age" min="18" max="100" value="{{ old('age') }}" placeholder="Cth: 21" required>
                @error('age') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label>Semester</label>
                <input type="number" name="semester" min="1" max="20" value="{{ old('semester') }}" placeholder="Cth: 3" required>
                @error('semester') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <label class="checkbox-row">
            <input type="checkbox" name="age_confirmed" value="1" {{ old('age_confirmed') ? 'checked' : '' }} required>
            <span>Saya mengesahkan bahawa saya berumur 18 tahun ke atas.</span>
        </label>

        <button class="btn" type="submit">Daftar & Mula Cari Jodoh 💞</button>
    </form>

    <div class="link-row">
        Dah ada akaun? <a href="{{ route('login') }}"><b>Log masuk</b></a>
    </div>
</div>

@endsection
