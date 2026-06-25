@extends('layouts.app')
@section('title', 'UNO Solo vs Bot - Carian Jodoh')
@section('content')

<div class="hero" style="padding-top:10px; padding-bottom:0;">
    <h1 style="font-size:19px;">🎴 UNO - Solo vs Bot</h1>
</div>

<div id="unoGame" style="padding-bottom:30px;">

    <div class="uno-bot-row">
        <div class="uno-bot-avatar">🤖</div>
        <div class="uno-bot-info">
            <div class="uno-bot-name">Bot</div>
            <div class="uno-bot-cards" id="botCardCount">7 kad</div>
        </div>
    </div>

    <div class="uno-table">
        <div class="uno-pile">
            <div class="uno-card uno-back" id="drawPile" title="Tarik kad">🎴</div>
            <div class="uno-pile-label">Tarik</div>
        </div>
        <div class="uno-pile">
            <div class="uno-card" id="discardCard"></div>
            <div class="uno-pile-label">Buang</div>
        </div>
    </div>

    <div class="uno-status" id="unoStatus">Sedang menyusun kad...</div>

    <div class="uno-hand" id="playerHand"></div>

    <div class="uno-actions">
        <button class="btn secondary" id="unoCallBtn" style="display:none;">📢 UNO!</button>
        <button class="btn secondary" id="restartBtn">🔄 Main Semula</button>
    </div>
</div>

<div id="colorModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3 style="margin:0 0 14px;">Pilih warna</h3>
        <div class="uno-color-pick">
            <button data-color="red" class="uno-color-btn" style="background:#e74c3c;"></button>
            <button data-color="yellow" class="uno-color-btn" style="background:#f1c40f;"></button>
            <button data-color="green" class="uno-color-btn" style="background:#2ecc71;"></button>
            <button data-color="blue" class="uno-color-btn" style="background:#3498db;"></button>
        </div>
    </div>
</div>

<div id="endModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div id="endIcon" style="font-size:40px;"></div>
        <h3 id="endTitle" style="margin:8px 0;"></h3>
        <p id="endBody" style="color:var(--text-soft); font-size:13px; margin:0 0 16px;"></p>
        <button class="btn" id="endPlayAgainBtn" style="width:100%;">Main Lagi</button>
        <a href="{{ route('game.uno.menu') }}" class="btn secondary" style="width:100%; margin-top:8px; display:block; text-align:center;">Kembali ke Menu</a>
    </div>
</div>

<style>
    .uno-bot-row { display:flex; align-items:center; gap:10px; padding:10px 4px; }
    .uno-bot-avatar { width:42px; height:42px; border-radius:50%; background:var(--grad-plum); display:flex; align-items:center; justify-content:center; font-size:20px; }
    .uno-bot-name { font-weight:700; font-size:14px; }
    .uno-bot-cards { font-size:12px; color:var(--text-soft); }

    .uno-table { display:flex; justify-content:center; gap:30px; padding:18px 0; }
    .uno-pile { text-align:center; }
    .uno-pile-label { font-size:11px; color:var(--text-soft); margin-top:6px; }

    .uno-card {
        width:64px; height:92px; border-radius:10px; display:flex; align-items:center; justify-content:center;
        font-size:26px; font-weight:800; color:#fff; box-shadow:0 4px 10px rgba(0,0,0,0.18);
        border: 3px solid #fff; user-select:none; position:relative;
    }
    .uno-back { background: linear-gradient(135deg, #222 0%, #555 100%); cursor:pointer; font-size:22px; }
    .uno-back:active { transform: scale(0.96); }

    .uno-card.c-red { background:#e74c3c; }
    .uno-card.c-yellow { background:#f1c40f; color:#3a2c00; }
    .uno-card.c-green { background:#2ecc71; }
    .uno-card.c-blue { background:#3498db; }
    .uno-card.c-black { background:#222; }

    .uno-status { text-align:center; font-size:13px; font-weight:700; color:var(--plum-deep); min-height:18px; margin-bottom:10px; }

    .uno-hand { display:flex; gap:8px; overflow-x:auto; padding:10px 6px 16px; }
    .uno-hand .uno-card { flex-shrink:0; cursor:pointer; transition: transform .12s; width:54px; height:80px; font-size:20px; }
    .uno-hand .uno-card:active { transform: translateY(-6px); }
    .uno-hand .uno-card.disabled { opacity:.4; cursor:not-allowed; }

    .uno-actions { display:flex; gap:10px; justify-content:center; padding:6px 16px 30px; }
    .uno-actions .btn { flex:1; max-width:160px; }

    .uno-color-pick { display:flex; gap:12px; justify-content:center; }
    .uno-color-btn { width:54px; height:54px; border-radius:50%; border:3px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.2); cursor:pointer; }
</style>

<script>
(function () {
    const COLORS = ['red', 'yellow', 'green', 'blue'];
    const COLOR_LABEL = { red: 'Merah', yellow: 'Kuning', green: 'Hijau', blue: 'Biru' };

    let deck = [];
    let discardPile = [];
    let playerHand = [];
    let botHand = [];
    let currentColor = null;
    let turn = 'player'; // 'player' | 'bot'
    let pendingWildCard = null;
    let gameOver = false;
    let drawnThisTurn = false;

    const statusEl = document.getElementById('unoStatus');
    const playerHandEl = document.getElementById('playerHand');
    const discardEl = document.getElementById('discardCard');
    const botCardCountEl = document.getElementById('botCardCount');
    const drawPileEl = document.getElementById('drawPile');
    const colorModal = document.getElementById('colorModal');
    const endModal = document.getElementById('endModal');
    const unoCallBtn = document.getElementById('unoCallBtn');

    function buildDeck() {
        const d = [];
        COLORS.forEach(color => {
            d.push({ color, value: '0', type: 'number' });
            for (let n = 1; n <= 9; n++) {
                d.push({ color, value: String(n), type: 'number' });
                d.push({ color, value: String(n), type: 'number' });
            }
            ['skip', 'reverse', 'draw2'].forEach(type => {
                d.push({ color, value: type, type });
                d.push({ color, value: type, type });
            });
        });
        for (let i = 0; i < 4; i++) {
            d.push({ color: 'black', value: 'wild', type: 'wild' });
            d.push({ color: 'black', value: 'wild4', type: 'wild4' });
        }
        return shuffle(d);
    }

    function shuffle(arr) {
        const a = arr.slice();
        for (let i = a.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [a[i], a[j]] = [a[j], a[i]];
        }
        return a;
    }

    function drawCard(n = 1) {
        const cards = [];
        for (let i = 0; i < n; i++) {
            if (deck.length === 0) {
                // Kitar semula pile buangan (kekalkan kad atas) bila deck habis.
                const top = discardPile.pop();
                deck = shuffle(discardPile);
                discardPile = [top];
            }
            cards.push(deck.pop());
        }
        return cards;
    }

    function cardLabel(card) {
        if (card.type === 'number') return card.value;
        if (card.type === 'skip') return '⃠';
        if (card.type === 'reverse') return '⇄';
        if (card.type === 'draw2') return '+2';
        if (card.type === 'wild') return '★';
        if (card.type === 'wild4') return '+4';
        return '?';
    }

    function renderCardEl(card, faceDown = false) {
        const el = document.createElement('div');
        el.className = 'uno-card ' + (faceDown ? 'uno-back' : 'c-' + card.color);
        el.textContent = faceDown ? '🎴' : cardLabel(card);
        return el;
    }

    function canPlay(card) {
        if (gameOver || turn !== 'player') return false;
        const top = discardPile[discardPile.length - 1];
        if (card.type === 'wild' || card.type === 'wild4') return true;
        return card.color === currentColor || card.value === top.value;
    }

    function startGame() {
        deck = buildDeck();
        playerHand = drawCard(7);
        botHand = drawCard(7);
        discardPile = [];
        let first = drawCard(1)[0];
        while (first.type === 'wild4') { // elak mula dengan wild4
            deck.push(first);
            deck = shuffle(deck);
            first = drawCard(1)[0];
        }
        discardPile.push(first);
        currentColor = first.color === 'black' ? COLORS[Math.floor(Math.random() * 4)] : first.color;
        if (first.type === 'wild') currentColor = COLORS[Math.floor(Math.random() * 4)];
        turn = 'player';
        gameOver = false;
        drawnThisTurn = false;

        // Kalau kad pertama action card, kira efeknya.
        if (first.type === 'skip' || first.type === 'reverse') {
            turn = 'player'; // 1v1 - skip/reverse pada bot bermakna player main dulu (tak berubah)
        } else if (first.type === 'draw2') {
            playerHand.push(...drawCard(2));
            turn = 'player';
        }

        renderAll();
        setStatus('Permainan bermula! Giliran anda.');
    }

    function setStatus(text) {
        statusEl.textContent = text;
    }

    function renderAll() {
        // Discard pile top
        discardEl.className = 'uno-card c-' + (discardPile[discardPile.length - 1].type.startsWith('wild') ? currentColor : discardPile[discardPile.length - 1].color);
        discardEl.textContent = cardLabel(discardPile[discardPile.length - 1]);

        // Bot count
        botCardCountEl.textContent = botHand.length + ' kad';

        // Player hand
        playerHandEl.innerHTML = '';
        playerHand.forEach((card, idx) => {
            const el = renderCardEl(card);
            if (!canPlay(card)) el.classList.add('disabled');
            el.addEventListener('click', () => onPlayerPlay(idx));
            playerHandEl.appendChild(el);
        });

        unoCallBtn.style.display = (playerHand.length === 1 && turn === 'player') ? 'inline-block' : 'none';
    }

    function onPlayerPlay(idx) {
        if (gameOver || turn !== 'player') return;
        const card = playerHand[idx];
        if (!canPlay(card)) return;

        playerHand.splice(idx, 1);

        if (card.type === 'wild' || card.type === 'wild4') {
            pendingWildCard = card;
            colorModal.style.display = 'flex';
            return;
        }

        playCardEffect(card, 'player');
    }

    function finishPlayerWildPlay(chosenColor) {
        const card = pendingWildCard;
        pendingWildCard = null;
        colorModal.style.display = 'none';
        playCardEffect(card, 'player', chosenColor);
    }

    function playCardEffect(card, who, chosenColor) {
        discardPile.push(card);
        currentColor = chosenColor || (card.color !== 'black' ? card.color : currentColor);

        if (checkWin(who)) return;

        if (card.type === 'skip') {
            setStatus(who === 'player' ? 'Anda skip bot - giliran anda lagi!' : 'Bot skip giliran anda!');
            turn = who === 'player' ? 'player' : 'player'; // 1v1: skip balik ke orang yg main
        } else if (card.type === 'reverse') {
            setStatus('Reverse! (1v1 = macam skip)');
            turn = who; // tetap giliran yang main dalam 1v1
        } else if (card.type === 'draw2') {
            if (who === 'player') {
                botHand.push(...drawCard(2));
                setStatus('Bot kena tarik 2 kad & skip giliran!');
                turn = 'player';
            } else {
                playerHand.push(...drawCard(2));
                setStatus('Anda kena tarik 2 kad & skip giliran!');
                turn = 'bot';
            }
        } else if (card.type === 'wild4') {
            if (who === 'player') {
                botHand.push(...drawCard(4));
                setStatus('Bot kena tarik 4 kad! Warna: ' + COLOR_LABEL[currentColor]);
                turn = 'player';
            } else {
                playerHand.push(...drawCard(4));
                setStatus('Anda kena tarik 4 kad! Warna: ' + COLOR_LABEL[currentColor]);
                turn = 'bot';
            }
        } else {
            turn = who === 'player' ? 'bot' : 'player';
            setStatus(who === 'player' ? 'Giliran Bot...' : 'Giliran anda!');
        }

        drawnThisTurn = false;
        renderAll();

        if (!gameOver && turn === 'bot') {
            setTimeout(botTurn, 900);
        }
    }

    function checkWin(who) {
        if (who === 'player' && playerHand.length === 0) {
            endGame(true);
            return true;
        }
        if (who === 'bot' && botHand.length === 0) {
            endGame(false);
            return true;
        }
        return false;
    }

    function endGame(playerWon) {
        gameOver = true;
        renderAll();
        document.getElementById('endIcon').textContent = playerWon ? '🎉' : '🤖';
        document.getElementById('endTitle').textContent = playerWon ? 'Anda Menang!' : 'Bot Menang!';
        document.getElementById('endBody').textContent = playerWon
            ? 'Tahniah, anda berjaya habiskan semua kad dulu!'
            : 'Jangan putus asa, cuba lagi sekali!';
        endModal.style.display = 'flex';
    }

    function botTurn() {
        if (gameOver) return;
        const top = discardPile[discardPile.length - 1];
        let playableIdx = botHand.findIndex(c =>
            c.type !== 'wild' && c.type !== 'wild4' && (c.color === currentColor || c.value === top.value)
        );
        if (playableIdx === -1) {
            playableIdx = botHand.findIndex(c => c.type === 'wild' || c.type === 'wild4');
        }

        if (playableIdx === -1) {
            // Bot tarik kad
            const drawn = drawCard(1);
            botHand.push(...drawn);
            const card = drawn[0];
            const canPlayDrawn = card.type === 'wild' || card.type === 'wild4' || card.color === currentColor || card.value === top.value;
            if (canPlayDrawn) {
                botHand.splice(botHand.length - 1, 1);
                playCardFromBot(card);
            } else {
                setStatus('Bot tarik kad & tak boleh main. Giliran anda!');
                turn = 'player';
                renderAll();
            }
            return;
        }

        const card = botHand[playableIdx];
        botHand.splice(playableIdx, 1);
        playCardFromBot(card);
    }

    function playCardFromBot(card) {
        let chosenColor = null;
        if (card.type === 'wild' || card.type === 'wild4') {
            // Bot pilih warna paling banyak dalam tangan dia.
            const counts = { red: 0, yellow: 0, green: 0, blue: 0 };
            botHand.forEach(c => { if (counts[c.color] !== undefined) counts[c.color]++; });
            chosenColor = Object.keys(counts).reduce((a, b) => counts[a] >= counts[b] ? a : b);
        }
        playCardEffect(card, 'bot', chosenColor);
    }

    drawPileEl.addEventListener('click', () => {
        if (gameOver || turn !== 'player' || drawnThisTurn) return;
        const drawn = drawCard(1);
        playerHand.push(...drawn);
        drawnThisTurn = true;
        const top = discardPile[discardPile.length - 1];
        const card = drawn[0];
        const canPlayDrawn = card.type === 'wild' || card.type === 'wild4' || card.color === currentColor || card.value === top.value;
        if (!canPlayDrawn) {
            setStatus('Tak boleh main kad yang ditarik. Giliran Bot...');
            turn = 'bot';
            renderAll();
            setTimeout(botTurn, 900);
        } else {
            setStatus('Kad ditarik boleh dimainkan kalau anda nak!');
            renderAll();
        }
    });

    document.querySelectorAll('.uno-color-btn').forEach(btn => {
        btn.addEventListener('click', () => finishPlayerWildPlay(btn.dataset.color));
    });

    unoCallBtn.addEventListener('click', () => {
        setStatus('UNO! 📢');
    });

    document.getElementById('restartBtn').addEventListener('click', startGame);
    document.getElementById('endPlayAgainBtn').addEventListener('click', () => {
        endModal.style.display = 'none';
        startGame();
    });

    startGame();
})();
</script>

@endsection
