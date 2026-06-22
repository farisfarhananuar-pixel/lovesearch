@extends('layouts.app')
@section('title', 'Sedang Mencari... - Carian Jodoh')
@section('content')

<div style="padding-top:40px;">
    <div class="pulse-ring">
        <span style="font-size:50px;">💓</span>
    </div>
    <div class="waiting-text">
        <h2>Sedang mencari jodoh anda...</h2>
        <p>Sila tunggu sebentar, sistem sedang padankan anda dengan seseorang yang sesuai.</p>
    </div>
</div>

<form id="cancelForm" method="POST" action="{{ route('match.waiting.cancel') }}" style="margin-top:30px;">
    @csrf
    <button class="btn danger" type="submit">Batalkan Carian</button>
</form>

@endsection

@push('scripts')
<script>
(function poll() {
    fetch('{{ route("match.waiting.poll") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.matched) {
                window.location.href = '/match/' + data.match_id;
            } else {
                setTimeout(poll, 2500);
            }
        })
        .catch(() => setTimeout(poll, 3500));
})();
</script>
@endpush
