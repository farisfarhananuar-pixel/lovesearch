@extends('layouts.app')
@section('title', 'Chess - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:10px; padding-bottom:0;">
    <h1 style="font-size:19px;">♟️ Chess</h1>
</div>

@if ($room->status === 'waiting')

    @if ($player->status === 'invited')
        <div class="card" style="text-align:center;">
            <p style="margin-bottom:14px;">{{ $room->creator->displayName() }} menjemput anda main chess. Terima jemputan?</p>
            <div style="display:flex; gap:10px;">
                <form method="POST" action="{{ route('game.chess.accept', $room->id) }}" style="flex:1;">
                    @csrf
                    <button class="btn" type="submit" style="width:100%;">Terima & Mula</button>
                </form>
                <form method="POST" action="{{ route('game.chess.decline', $room->id) }}" style="flex:1;">
                    @csrf
                    <button class="btn secondary" type="submit" style="width:100%;">Tolak</button>
                </form>
            </div>
        </div>
    @else
        <div class="card" style="text-align:center;">
            <p>Menunggu {{ $opponent?->user?->displayName() }} terima jemputan...</p>
            <a href="{{ route('game.chess.menu') }}" class="btn secondary" style="margin-top:12px; display:inline-block;">Kembali ke Menu</a>
        </div>
    @endif

@else

<div id="chessGame" style="padding-bottom:30px;">
    <div class="uno-bot-row">
        <div class="uno-bot-avatar">{{ $vsBot ? '🤖' : ($opponent?->user?->avatarFallback() ?? '🙂') }}</div>
        <div class="uno-bot-info">
            <div class="uno-bot-name">{{ $vsBot ? 'Bot' : $opponent?->user?->displayName() }}</div>
            <div class="uno-bot-cards">Bermain sebagai {{ $myColor === 'w' ? 'Hitam ♚' : 'Putih ♔' }}</div>
        </div>
    </div>

    <div class="chess-status" id="chessStatus">Memuatkan...</div>

    <div class="chess-board" id="chessBoard"></div>

    <div class="uno-actions">
        @if ($room->status === 'active')
        <button class="btn secondary" id="resignBtn">🏳️ Mengalah</button>
        @endif
        @if ($room->status === 'finished')
        <a href="{{ route('game.chess.menu') }}" class="btn" style="text-decoration:none; text-align:center;">Kembali ke Menu</a>
        @endif
    </div>
</div>

<div id="promoModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3 style="margin:0 0 14px;">Pilih kad utk penaikan pangkat</h3>
        <div class="uno-color-pick" id="promoChoices"></div>
    </div>
</div>

<div id="chessEndModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div id="chessEndIcon" style="font-size:40px;"></div>
        <h3 id="chessEndTitle" style="margin:8px 0;"></h3>
        <a href="{{ route('game.chess.menu') }}" class="btn" style="width:100%; display:block; text-align:center; text-decoration:none;">Kembali ke Menu</a>
    </div>
</div>

<style>
    .chess-status { text-align:center; font-size:13px; font-weight:700; color:var(--plum-deep); min-height:18px; margin:10px 0; }
    .chess-board {
        display:grid; grid-template-columns:repeat(8, 1fr); width:min(94vw, 420px);
        aspect-ratio:1; margin:0 auto; border-radius:10px; overflow:hidden;
        box-shadow:0 6px 18px rgba(0,0,0,0.18); border:3px solid #fff;
    }
    .chess-sq {
        display:flex; align-items:center; justify-content:center; position:relative;
        font-size:min(7vw, 30px); user-select:none; cursor:pointer;
    }
    .chess-sq.light { background:#f0d9b5; }
    .chess-sq.dark { background:#946f51; }
    .chess-sq.selected { outline:3px solid #ff5d8f; outline-offset:-3px; z-index:2; }
    .chess-sq.last-from, .chess-sq.last-to { background-color:#f7e98e; }
    .chess-sq .dot { position:absolute; width:26%; height:26%; border-radius:50%; background:rgba(80,40,10,0.45); }
    .chess-sq .dot.capture { width:90%; height:90%; background:none; border:4px solid rgba(200,40,40,0.6); border-radius:50%; }
    .chess-sq .piece { line-height:1; filter:drop-shadow(0 1px 1px rgba(0,0,0,0.3)); }

    .uno-color-pick button { width:54px; height:54px; border-radius:50%; border:3px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.2); cursor:pointer; font-size:24px; background:var(--grad-plum); color:#fff; }
</style>

<script>
(function () {
    const roomId = {{ $room->id }};
    const myColor = '{{ $myColor }}';
    const vsBot = {{ $vsBot ? 'true' : 'false' }};
    let initialStatus = '{{ $room->status }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || '{{ csrf_token() }}';

    const urls = {
        poll: '{{ route('game.chess.poll', $room->id) }}',
        legalMoves: '{{ route('game.chess.legal-moves', $room->id) }}',
        move: '{{ route('game.chess.move', $room->id) }}',
        resign: '{{ route('game.chess.resign', $room->id) }}',
    };

    const PIECE_GLYPH = { K: '♔', Q: '♕', R: '♖', B: '♗', N: '♘', P: '♙', k: '♚', q: '♛', r: '♜', b: '♝', n: '♞', p: '♟' };
    const PROMO_PIECES = [
        { code: 'Q', label: '♕' }, { code: 'R', label: '♖' }, { code: 'B', label: '♗' }, { code: 'N', label: '♘' },
    ];

    let state = @json($room->state);
    let selected = null;
    let highlights = [];
    let busy = false;

    const boardEl = document.getElementById('chessBoard');
    const statusEl = document.getElementById('chessStatus');
    const endModal = document.getElementById('chessEndModal');
    const promoModal = document.getElementById('promoModal');
    const promoChoicesEl = document.getElementById('promoChoices');
    const resignBtn = document.getElementById('resignBtn');

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(body || {}),
        }).then(r => r.json());
    }

    function get(url) {
        return fetch(url, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
    }

    function squareKey(r, c) { return r + '_' + c; }

    function isMyPiece(piece) {
        if (!piece) return false;
        const isWhite = piece === piece.toUpperCase();
        return (isWhite && myColor === 'w') || (!isWhite && myColor === 'b');
    }

    function renderBoard() {
        boardEl.innerHTML = '';
        const rows = myColor === 'w' ? [7, 6, 5, 4, 3, 2, 1, 0] : [0, 1, 2, 3, 4, 5, 6, 7];
        const cols = myColor === 'w' ? [0, 1, 2, 3, 4, 5, 6, 7] : [7, 6, 5, 4, 3, 2, 1, 0];

        const highlightMap = {};
        highlights.forEach(h => { highlightMap[squareKey(h.row, h.col)] = h; });

        const lastMove = state.lastMove;

        rows.forEach(r => {
            cols.forEach(c => {
                const sq = document.createElement('div');
                sq.className = 'chess-sq ' + ((r + c) % 2 === 0 ? 'light' : 'dark');
                sq.dataset.row = r;
                sq.dataset.col = c;

                if (lastMove && ((lastMove.from[0] === r && lastMove.from[1] === c) || (lastMove.to[0] === r && lastMove.to[1] === c))) {
                    sq.classList.add('last-to');
                }
                if (selected && selected[0] === r && selected[1] === c) {
                    sq.classList.add('selected');
                }

                const piece = state.board[r][c];
                if (piece) {
                    const span = document.createElement('span');
                    span.className = 'piece';
                    span.textContent = PIECE_GLYPH[piece];
                    sq.appendChild(span);
                }

                const h = highlightMap[squareKey(r, c)];
                if (h) {
                    const dot = document.createElement('div');
                    dot.className = 'dot' + (piece ? ' capture' : '');
                    sq.appendChild(dot);
                }

                sq.addEventListener('click', () => onSquareClick(r, c));
                boardEl.appendChild(sq);
            });
        });
    }

    function setStatusText() {
        if (state.status === 'checkmate') {
            statusEl.textContent = state.winner === myColor ? 'Checkmate! Anda menang 🎉' : 'Checkmate! Anda kalah.';
        } else if (state.status === 'stalemate') {
            statusEl.textContent = 'Stalemate - permainan seri.';
        } else if (state.status === 'draw') {
            statusEl.textContent = 'Seri (50 langkah tanpa tangkapan).';
        } else if (state.turn === myColor) {
            statusEl.textContent = 'Giliran anda' + (state.inCheck ? ' - Anda kena CHECK!' : '');
        } else {
            statusEl.textContent = (state.inCheck ? '(Lawan kena check) ' : '') + (vsBot ? 'Giliran Bot...' : 'Menunggu lawan...');
        }
    }

    function maybeShowEndModal() {
        if (!['checkmate', 'stalemate', 'draw'].includes(state.status)) return;
        let icon = '🤝';
        let title = 'Permainan Seri';
        if (state.status === 'checkmate') {
            icon = state.winner === myColor ? '🎉' : (vsBot ? '🤖' : '😔');
            title = state.winner === myColor ? 'Anda Menang!' : 'Anda Kalah';
        }
        document.getElementById('chessEndIcon').textContent = icon;
        document.getElementById('chessEndTitle').textContent = title;
        endModal.style.display = 'flex';
    }

    function onSquareClick(r, c) {
        if (busy || state.status !== 'active' || state.turn !== myColor) return;

        if (selected) {
            const target = highlights.find(h => h.row === r && h.col === c);
            if (target) {
                if (target.promotion) {
                    showPromotionPicker(selected, [r, c]);
                } else {
                    submitMove(selected, [r, c], null);
                }
                return;
            }
        }

        const piece = state.board[r][c];
        if (piece && isMyPiece(piece)) {
            selected = [r, c];
            fetchLegalMoves(r, c);
        } else {
            selected = null;
            highlights = [];
            renderBoard();
        }
    }

    function fetchLegalMoves(r, c) {
        post(urls.legalMoves, { row: r, col: c }).then(data => {
            highlights = data.moves || [];
            renderBoard();
        });
    }

    function showPromotionPicker(from, to) {
        promoChoicesEl.innerHTML = '';
        PROMO_PIECES.forEach(p => {
            const btn = document.createElement('button');
            btn.textContent = myColor === 'w' ? PIECE_GLYPH[p.code] : PIECE_GLYPH[p.code.toLowerCase()];
            btn.addEventListener('click', () => {
                promoModal.style.display = 'none';
                submitMove(from, to, p.code);
            });
            promoChoicesEl.appendChild(btn);
        });
        promoModal.style.display = 'flex';
    }

    function submitMove(from, to, promotion) {
        busy = true;
        post(urls.move, { from, to, promotion }).then(data => {
            busy = false;
            if (data.error) {
                statusEl.textContent = data.error;
                selected = null;
                highlights = [];
                renderBoard();
                return;
            }
            state = data.state;
            selected = null;
            highlights = [];
            renderBoard();
            setStatusText();
            maybeShowEndModal();
        }).catch(() => { busy = false; });
    }

    if (resignBtn) {
        resignBtn.addEventListener('click', () => {
            if (!confirm('Mengalah dari permainan ini?')) return;
            fetch(urls.resign, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            }).then(() => window.location.reload());
        });
    }

    let lastSeenSignature = '';
    function pollState() {
        get(urls.poll).then(data => {
            const sig = JSON.stringify([data.status, data.state.turn, data.state.lastMove]);
            if (sig !== lastSeenSignature) {
                lastSeenSignature = sig;
                state = data.state;
                selected = null;
                highlights = [];
                renderBoard();
                setStatusText();
                maybeShowEndModal();
            }
        }).finally(() => {
            if (state.status === 'active') setTimeout(pollState, 1800);
        });
    }

    renderBoard();
    setStatusText();
    maybeShowEndModal();
    lastSeenSignature = JSON.stringify([initialStatus, state.turn, state.lastMove]);

    if (!vsBot && state.status === 'active') {
        setTimeout(pollState, 1800);
    }
})();
</script>

@endif

@endsection
