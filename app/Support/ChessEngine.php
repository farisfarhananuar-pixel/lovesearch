<?php

namespace App\Support;

/**
 * Engine peraturan chess - reka bentuk sengaja "stateless" (semua kaedah static,
 * terima & pulangkan array biasa) supaya state boleh terus disimpan sebagai JSON
 * dalam lajur `game_rooms.state` dan dikongsi antara mod solo (lawan bot) &
 * mod multiplayer (lawan kawan) tanpa perlu tulis dua kali logik chess.
 *
 * Papan: array 8x8, board[row][col]. row 0 = rank 1 (baris asal putih),
 * row 7 = rank 8 (baris asal hitam). col 0 = file a ... col 7 = file h.
 * Huruf besar = putih (P N B R Q K), huruf kecil = hitam (p n b r q k), null = kosong.
 */
class ChessEngine
{
    private const PIECE_VALUE = ['P' => 1, 'N' => 3, 'B' => 3, 'R' => 5, 'Q' => 9, 'K' => 0];

    public static function initialState(): array
    {
        $board = array_fill(0, 8, array_fill(0, 8, null));

        $board[0] = ['R', 'N', 'B', 'Q', 'K', 'B', 'N', 'R'];
        $board[1] = array_fill(0, 8, 'P');
        $board[6] = array_fill(0, 8, 'p');
        $board[7] = ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r'];

        return [
            'board' => $board,
            'turn' => 'w',
            'castling' => ['K' => true, 'Q' => true, 'k' => true, 'q' => true],
            'enPassant' => null,
            'halfmoveClock' => 0,
            'fullmoveNumber' => 1,
            'status' => 'active',
            'winner' => null,
            'lastMove' => null,
            'inCheck' => false,
        ];
    }

    public static function pieceColor(string $piece): string
    {
        return ctype_upper($piece) ? 'w' : 'b';
    }

    private static function opposite(string $color): string
    {
        return $color === 'w' ? 'b' : 'w';
    }

    private static function inBounds(int $row, int $col): bool
    {
        return $row >= 0 && $row < 8 && $col >= 0 && $col < 8;
    }

    /**
     * Semua langkah sah (dah tolak yang biarkan raja sendiri kena check) untuk
     * satu petak. Setiap langkah: ['to' => [row,col], 'capture' => bool,
     * 'enPassant' => bool, 'castle' => null|'K'|'Q', 'promotion' => bool].
     */
    public static function legalMovesFrom(array $state, int $row, int $col): array
    {
        $board = $state['board'];
        $piece = $board[$row][$col] ?? null;

        if ($piece === null || self::pieceColor($piece) !== $state['turn']) {
            return [];
        }

        $color = $state['turn'];
        $pseudo = self::pseudoMoves($state, $row, $col);
        $legal = [];

        foreach ($pseudo as $move) {
            $simBoard = self::applyToBoard($board, [$row, $col], $move);
            if (! self::isInCheck($simBoard, $color)) {
                $legal[] = $move;
            }
        }

        return $legal;
    }

    /** Semua langkah sah untuk satu warna (utk checkmate/stalemate & bot). */
    public static function allLegalMoves(array $state, string $color): array
    {
        $moves = [];
        $board = $state['board'];

        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                $piece = $board[$r][$c];
                if ($piece !== null && self::pieceColor($piece) === $color) {
                    foreach (self::legalMovesFrom($state, $r, $c) as $move) {
                        $moves[] = ['from' => [$r, $c]] + $move;
                    }
                }
            }
        }

        return $moves;
    }

    /**
     * Cuba buat langkah dari $from ke $to. Pulangkan state baru kalau sah.
     * Throw InvalidArgumentException kalau tak sah.
     */
    public static function applyMove(array $state, array $from, array $to, ?string $promotion = null): array
    {
        [$fr, $fc] = $from;
        $candidates = self::legalMovesFrom($state, $fr, $fc);

        $chosen = null;
        foreach ($candidates as $move) {
            if ($move['to'][0] === $to[0] && $move['to'][1] === $to[1]) {
                $chosen = $move;
                break;
            }
        }

        if ($chosen === null) {
            throw new \InvalidArgumentException('Langkah tidak sah.');
        }

        $color = $state['turn'];
        $piece = $state['board'][$fr][$fc];
        $capturedPiece = $chosen['enPassant']
            ? $state['board'][$fr][$to[1]]
            : ($state['board'][$to[0]][$to[1]] ?? null);

        $promoPiece = null;
        if ($chosen['promotion']) {
            $choice = in_array(strtoupper((string) $promotion), ['Q', 'R', 'B', 'N'], true)
                ? strtoupper($promotion)
                : 'Q';
            $promoPiece = $color === 'w' ? $choice : strtolower($choice);
        }

        $newBoard = self::applyToBoard($state['board'], $from, $chosen, $promoPiece);

        // Kemaskini hak castling - kalau raja/rook asal dah bergerak, atau rook
        // ditangkap terus dari petak asalnya.
        $castling = $state['castling'];
        if ($piece === 'K') { $castling['K'] = false; $castling['Q'] = false; }
        if ($piece === 'k') { $castling['k'] = false; $castling['q'] = false; }
        if ($piece === 'R' && $fr === 0 && $fc === 0) { $castling['Q'] = false; }
        if ($piece === 'R' && $fr === 0 && $fc === 7) { $castling['K'] = false; }
        if ($piece === 'r' && $fr === 7 && $fc === 0) { $castling['q'] = false; }
        if ($piece === 'r' && $fr === 7 && $fc === 7) { $castling['k'] = false; }
        if ($to[0] === 0 && $to[1] === 0) { $castling['Q'] = false; }
        if ($to[0] === 0 && $to[1] === 7) { $castling['K'] = false; }
        if ($to[0] === 7 && $to[1] === 0) { $castling['q'] = false; }
        if ($to[0] === 7 && $to[1] === 7) { $castling['k'] = false; }

        // En passant target - hanya wujud sebaik-baik je pawn melangkah 2 petak.
        $enPassant = null;
        if (strtoupper($piece) === 'P' && abs($to[0] - $fr) === 2) {
            $enPassant = [(int) (($fr + $to[0]) / 2), $fc];
        }

        $isCapture = $capturedPiece !== null;
        $isPawnMove = strtoupper($piece) === 'P';
        $halfmove = ($isCapture || $isPawnMove) ? 0 : $state['halfmoveClock'] + 1;
        $fullmove = $color === 'b' ? $state['fullmoveNumber'] + 1 : $state['fullmoveNumber'];

        $nextColor = self::opposite($color);

        $newState = [
            'board' => $newBoard,
            'turn' => $nextColor,
            'castling' => $castling,
            'enPassant' => $enPassant,
            'halfmoveClock' => $halfmove,
            'fullmoveNumber' => $fullmove,
            'status' => 'active',
            'winner' => null,
            'lastMove' => [
                'from' => $from,
                'to' => $to,
                'piece' => $piece,
                'captured' => $capturedPiece,
                'promotion' => $promoPiece,
                'castle' => $chosen['castle'],
            ],
        ];

        $opponentInCheck = self::isInCheck($newBoard, $nextColor);
        $newState['inCheck'] = $opponentInCheck;

        $opponentHasMoves = count(self::allLegalMoves($newState, $nextColor)) > 0;

        if (! $opponentHasMoves) {
            if ($opponentInCheck) {
                $newState['status'] = 'checkmate';
                $newState['winner'] = $color;
            } else {
                $newState['status'] = 'stalemate';
            }
        } elseif ($halfmove >= 100) {
            $newState['status'] = 'draw';
        }

        return $newState;
    }

    /** Bot mudah - utamakan tangkapan & checkmate, sikit rawak supaya tak boring. */
    public static function pickBotMove(array $state): array
    {
        $color = $state['turn'];
        $moves = self::allLegalMoves($state, $color);

        if (empty($moves)) {
            throw new \RuntimeException('Tiada langkah untuk bot.');
        }

        $best = null;
        $bestScore = -INF;

        foreach ($moves as $move) {
            $promo = $move['promotion'] ? 'Q' : null;
            $simState = self::applyMove($state, $move['from'], $move['to'], $promo);

            $captured = $move['enPassant']
                ? $state['board'][$move['from'][0]][$move['to'][1]]
                : ($state['board'][$move['to'][0]][$move['to'][1]] ?? null);

            $score = 0;
            if ($captured !== null) {
                $score += (self::PIECE_VALUE[strtoupper($captured)] ?? 0) * 10;
            }
            if ($move['promotion']) {
                $score += 80;
            }
            if ($simState['status'] === 'checkmate' && $simState['winner'] === $color) {
                $score += 10000;
            } elseif (self::isInCheck($simState['board'], self::opposite($color))) {
                $score += 4;
            }
            $score += mt_rand(0, 25) / 10;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $move;
            }
        }

        return [
            'from' => $best['from'],
            'to' => $best['to'],
            'promotion' => $best['promotion'] ? 'Q' : null,
        ];
    }

    // ---------------------------------------------------------------------
    // Penjana langkah pseudo-legal (belum tapis check)
    // ---------------------------------------------------------------------

    private static function pseudoMoves(array $state, int $row, int $col): array
    {
        $board = $state['board'];
        $piece = $board[$row][$col];
        $type = strtoupper($piece);
        $color = self::pieceColor($piece);

        return match ($type) {
            'P' => self::pawnMoves($state, $row, $col, $color),
            'N' => self::stepMoves($board, $row, $col, $color, [
                [-2, -1], [-2, 1], [-1, -2], [-1, 2], [1, -2], [1, 2], [2, -1], [2, 1],
            ]),
            'B' => self::slideMoves($board, $row, $col, $color, [[-1, -1], [-1, 1], [1, -1], [1, 1]]),
            'R' => self::slideMoves($board, $row, $col, $color, [[-1, 0], [1, 0], [0, -1], [0, 1]]),
            'Q' => self::slideMoves($board, $row, $col, $color, [
                [-1, -1], [-1, 1], [1, -1], [1, 1], [-1, 0], [1, 0], [0, -1], [0, 1],
            ]),
            'K' => self::kingMoves($state, $row, $col, $color),
            default => [],
        };
    }

    private static function pawnMoves(array $state, int $row, int $col, string $color): array
    {
        $board = $state['board'];
        $dir = $color === 'w' ? 1 : -1;
        $startRow = $color === 'w' ? 1 : 6;
        $promoRow = $color === 'w' ? 7 : 0;
        $moves = [];

        $oneRow = $row + $dir;
        if (self::inBounds($oneRow, $col) && $board[$oneRow][$col] === null) {
            $moves[] = ['to' => [$oneRow, $col], 'capture' => false, 'enPassant' => false, 'castle' => null, 'promotion' => $oneRow === $promoRow];

            $twoRow = $row + 2 * $dir;
            if ($row === $startRow && $board[$twoRow][$col] === null) {
                $moves[] = ['to' => [$twoRow, $col], 'capture' => false, 'enPassant' => false, 'castle' => null, 'promotion' => false];
            }
        }

        foreach ([-1, 1] as $dc) {
            $r = $row + $dir;
            $c = $col + $dc;
            if (! self::inBounds($r, $c)) {
                continue;
            }

            $target = $board[$r][$c];
            if ($target !== null && self::pieceColor($target) !== $color) {
                $moves[] = ['to' => [$r, $c], 'capture' => true, 'enPassant' => false, 'castle' => null, 'promotion' => $r === $promoRow];
            } elseif ($target === null && $state['enPassant'] !== null && $state['enPassant'][0] === $r && $state['enPassant'][1] === $c) {
                $moves[] = ['to' => [$r, $c], 'capture' => true, 'enPassant' => true, 'castle' => null, 'promotion' => false];
            }
        }

        return $moves;
    }

    private static function stepMoves(array $board, int $row, int $col, string $color, array $offsets): array
    {
        $moves = [];
        foreach ($offsets as [$dr, $dc]) {
            $r = $row + $dr;
            $c = $col + $dc;
            if (! self::inBounds($r, $c)) {
                continue;
            }
            $target = $board[$r][$c];
            if ($target === null || self::pieceColor($target) !== $color) {
                $moves[] = ['to' => [$r, $c], 'capture' => $target !== null, 'enPassant' => false, 'castle' => null, 'promotion' => false];
            }
        }

        return $moves;
    }

    private static function slideMoves(array $board, int $row, int $col, string $color, array $directions): array
    {
        $moves = [];
        foreach ($directions as [$dr, $dc]) {
            $r = $row + $dr;
            $c = $col + $dc;
            while (self::inBounds($r, $c)) {
                $target = $board[$r][$c];
                if ($target === null) {
                    $moves[] = ['to' => [$r, $c], 'capture' => false, 'enPassant' => false, 'castle' => null, 'promotion' => false];
                } else {
                    if (self::pieceColor($target) !== $color) {
                        $moves[] = ['to' => [$r, $c], 'capture' => true, 'enPassant' => false, 'castle' => null, 'promotion' => false];
                    }
                    break;
                }
                $r += $dr;
                $c += $dc;
            }
        }

        return $moves;
    }

    private static function kingMoves(array $state, int $row, int $col, string $color): array
    {
        $board = $state['board'];
        $moves = self::stepMoves($board, $row, $col, $color, [
            [-1, -1], [-1, 0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1, 1],
        ]);

        if (self::isInCheck($board, $color)) {
            return $moves; // tak boleh castle semasa check
        }

        $homeRow = $color === 'w' ? 0 : 7;
        $kingsideRight = $color === 'w' ? 'K' : 'k';
        $queensideRight = $color === 'w' ? 'Q' : 'q';
        $opp = self::opposite($color);

        if ($row === $homeRow && $col === 4) {
            if (
                $state['castling'][$kingsideRight]
                && $board[$homeRow][5] === null && $board[$homeRow][6] === null
                && ! self::isSquareAttacked($board, $homeRow, 5, $opp)
                && ! self::isSquareAttacked($board, $homeRow, 6, $opp)
            ) {
                $moves[] = ['to' => [$homeRow, 6], 'capture' => false, 'enPassant' => false, 'castle' => 'K', 'promotion' => false];
            }

            if (
                $state['castling'][$queensideRight]
                && $board[$homeRow][1] === null && $board[$homeRow][2] === null && $board[$homeRow][3] === null
                && ! self::isSquareAttacked($board, $homeRow, 3, $opp)
                && ! self::isSquareAttacked($board, $homeRow, 2, $opp)
            ) {
                $moves[] = ['to' => [$homeRow, 2], 'capture' => false, 'enPassant' => false, 'castle' => 'Q', 'promotion' => false];
            }
        }

        return $moves;
    }

    // ---------------------------------------------------------------------
    // Bantuan papan / check detection
    // ---------------------------------------------------------------------

    private static function applyToBoard(array $board, array $from, array $move, ?string $promoPiece = null): array
    {
        [$fr, $fc] = $from;
        [$tr, $tc] = $move['to'];
        $piece = $board[$fr][$fc];

        if ($move['enPassant']) {
            $board[$fr][$tc] = null; // buang pawn yang ditangkap (duduk sebaris dgn 'from')
        }

        $board[$tr][$tc] = $promoPiece ?? $piece;
        $board[$fr][$fc] = null;

        if ($move['castle'] === 'K') {
            $board[$tr][5] = $board[$tr][7];
            $board[$tr][7] = null;
        } elseif ($move['castle'] === 'Q') {
            $board[$tr][3] = $board[$tr][0];
            $board[$tr][0] = null;
        }

        return $board;
    }

    private static function findKing(array $board, string $color): array
    {
        $target = $color === 'w' ? 'K' : 'k';
        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                if ($board[$r][$c] === $target) {
                    return [$r, $c];
                }
            }
        }

        return [-1, -1]; // tak sepatutnya berlaku - raja mesti wujud
    }

    public static function isInCheck(array $board, string $color): bool
    {
        [$kr, $kc] = self::findKing($board, $color);
        if ($kr === -1) {
            return false;
        }

        return self::isSquareAttacked($board, $kr, $kc, self::opposite($color));
    }

    private static function isSquareAttacked(array $board, int $row, int $col, string $byColor): bool
    {
        // Pawn
        $dir = $byColor === 'w' ? 1 : -1;
        $pawnChar = $byColor === 'w' ? 'P' : 'p';
        foreach ([-1, 1] as $dc) {
            $r = $row - $dir;
            $c = $col + $dc;
            if (self::inBounds($r, $c) && $board[$r][$c] === $pawnChar) {
                return true;
            }
        }

        // Knight
        $knightChar = $byColor === 'w' ? 'N' : 'n';
        foreach ([[-2, -1], [-2, 1], [-1, -2], [-1, 2], [1, -2], [1, 2], [2, -1], [2, 1]] as [$dr, $dc]) {
            $r = $row + $dr;
            $c = $col + $dc;
            if (self::inBounds($r, $c) && $board[$r][$c] === $knightChar) {
                return true;
            }
        }

        // King
        $kingChar = $byColor === 'w' ? 'K' : 'k';
        foreach ([[-1, -1], [-1, 0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1, 1]] as [$dr, $dc]) {
            $r = $row + $dr;
            $c = $col + $dc;
            if (self::inBounds($r, $c) && $board[$r][$c] === $kingChar) {
                return true;
            }
        }

        // Sliding: diagonal (bishop/queen) & orthogonal (rook/queen)
        $diagPieces = $byColor === 'w' ? ['B', 'Q'] : ['b', 'q'];
        foreach ([[-1, -1], [-1, 1], [1, -1], [1, 1]] as [$dr, $dc]) {
            $r = $row + $dr;
            $c = $col + $dc;
            while (self::inBounds($r, $c)) {
                $target = $board[$r][$c];
                if ($target !== null) {
                    if (in_array($target, $diagPieces, true)) {
                        return true;
                    }
                    break;
                }
                $r += $dr;
                $c += $dc;
            }
        }

        $orthoPieces = $byColor === 'w' ? ['R', 'Q'] : ['r', 'q'];
        foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dr, $dc]) {
            $r = $row + $dr;
            $c = $col + $dc;
            while (self::inBounds($r, $c)) {
                $target = $board[$r][$c];
                if ($target !== null) {
                    if (in_array($target, $orthoPieces, true)) {
                        return true;
                    }
                    break;
                }
                $r += $dr;
                $c += $dc;
            }
        }

        return false;
    }
}
