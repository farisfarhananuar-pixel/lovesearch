<?php

namespace App\Support;

/**
 * Engine UNO untuk mod "main dengan kawan" (2-6 orang). Tak macam Chess, papan
 * UNO ada maklumat tersembunyi (tangan kad orang lain), jadi server WAJIB jadi
 * pihak berkuasa penuh - klien tak boleh dipercayai utk kira tangan sendiri.
 *
 * Peraturan asas diport terus dari game/uno-solo.blade.php (mod solo lawan bot)
 * supaya rasa permainan konsisten, tapi turn-rotation diperluas utk N pemain.
 */
class UnoEngine
{
    private const COLORS = ['red', 'yellow', 'green', 'blue'];

    public static function buildDeck(): array
    {
        $deck = [];
        foreach (self::COLORS as $color) {
            $deck[] = ['color' => $color, 'value' => '0', 'type' => 'number'];
            for ($n = 1; $n <= 9; $n++) {
                $deck[] = ['color' => $color, 'value' => (string) $n, 'type' => 'number'];
                $deck[] = ['color' => $color, 'value' => (string) $n, 'type' => 'number'];
            }
            foreach (['skip', 'reverse', 'draw2'] as $type) {
                $deck[] = ['color' => $color, 'value' => $type, 'type' => $type];
                $deck[] = ['color' => $color, 'value' => $type, 'type' => $type];
            }
        }
        for ($i = 0; $i < 4; $i++) {
            $deck[] = ['color' => 'black', 'value' => 'wild', 'type' => 'wild'];
            $deck[] = ['color' => 'black', 'value' => 'wild4', 'type' => 'wild4'];
        }
        shuffle($deck);

        return $deck;
    }

    /** Mula permainan baru. $userIds = senarai id ikut turutan tempat duduk. */
    public static function deal(array $userIds): array
    {
        $deck = self::buildDeck();
        $hands = [];

        foreach ($userIds as $uid) {
            $hands[(string) $uid] = array_splice($deck, 0, 7);
        }

        // Buang kad istimewa drpd kad pembuka - elak kerumitan kesan istimewa
        // pusingan pertama (siapa "kena skip" sebelum sesiapa pun main lagi).
        $first = array_shift($deck);
        while ($first['type'] !== 'number') {
            $deck[] = $first;
            shuffle($deck);
            $first = array_shift($deck);
        }

        return [
            'deck' => $deck,
            'discard' => [$first],
            'hands' => $hands,
            'order' => array_map('intval', $userIds),
            'turnIndex' => 0,
            'direction' => 1,
            'currentColor' => $first['color'],
            'drawnThisTurn' => false,
            'status' => 'active',
            'winnerId' => null,
            'calledUno' => [],
            'log' => ["Permainan dimulakan! Kad pembuka: {$first['color']} {$first['value']}."],
        ];
    }

    public static function currentPlayerId(array $state): int
    {
        return $state['order'][$state['turnIndex']];
    }

    private static function mod(int $n, int $m): int
    {
        return (($n % $m) + $m) % $m;
    }

    private static function isPlayable(array $card, array $discardTop, string $currentColor): bool
    {
        if ($card['type'] === 'wild' || $card['type'] === 'wild4') {
            return true;
        }

        return $card['color'] === $currentColor || $card['value'] === $discardTop['value'];
    }

    private static function drawFromDeck(array &$state, int $n): array
    {
        $drawn = [];
        for ($i = 0; $i < $n; $i++) {
            if (empty($state['deck'])) {
                $top = array_pop($state['discard']);
                $state['deck'] = $state['discard'];
                shuffle($state['deck']);
                $state['discard'] = [$top];
            }
            if (empty($state['deck'])) {
                break; // amat jarang - semua kad sedang di tangan pemain
            }
            $drawn[] = array_pop($state['deck']);
        }

        return $drawn;
    }

    private static function giveCards(array &$state, int $userId, int $n): void
    {
        $cards = self::drawFromDeck($state, $n);
        $state['hands'][(string) $userId] = array_merge($state['hands'][(string) $userId], $cards);
        $state['calledUno'] = array_values(array_diff($state['calledUno'], [$userId]));
    }

    /** Alih giliran ke pemain seterusnya, kira kesan kad (skip/reverse/draw2/wild4). */
    private static function advanceTurn(array &$state, string $cardType): void
    {
        $count = count($state['order']);
        if ($count < 2) {
            return;
        }

        $dir = $state['direction'];
        $cur = $state['turnIndex'];

        switch ($cardType) {
            case 'reverse':
                if ($count === 2) {
                    $state['turnIndex'] = self::mod($cur + $dir * 2, $count);
                } else {
                    $state['direction'] = -$dir;
                    $state['turnIndex'] = self::mod($cur + (-$dir), $count);
                }
                break;

            case 'skip':
                $state['turnIndex'] = self::mod($cur + $dir * 2, $count);
                break;

            case 'draw2':
                $targetIdx = self::mod($cur + $dir, $count);
                self::giveCards($state, $state['order'][$targetIdx], 2);
                $state['log'][] = self::label($state['order'][$targetIdx]).' kena tarik 2 kad & giliran diskip.';
                $state['turnIndex'] = self::mod($targetIdx + $dir, $count);
                break;

            case 'wild4':
                $targetIdx = self::mod($cur + $dir, $count);
                self::giveCards($state, $state['order'][$targetIdx], 4);
                $state['log'][] = self::label($state['order'][$targetIdx]).' kena tarik 4 kad & giliran diskip.';
                $state['turnIndex'] = self::mod($targetIdx + $dir, $count);
                break;

            default:
                $state['turnIndex'] = self::mod($cur + $dir, $count);
        }

        $state['drawnThisTurn'] = false;
    }

    private static function label(int $userId): string
    {
        return 'Pemain #'.$userId; // ditukar jadi nama sebenar oleh controller semasa render log
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function play(array $state, int $userId, int $cardIndex, ?string $chosenColor = null): array
    {
        if ($state['status'] !== 'active') {
            throw new \InvalidArgumentException('Permainan sudah tamat.');
        }
        if (self::currentPlayerId($state) !== $userId) {
            throw new \InvalidArgumentException('Bukan giliran anda.');
        }

        $hand = $state['hands'][(string) $userId];
        if (! array_key_exists($cardIndex, $hand)) {
            throw new \InvalidArgumentException('Kad tidak sah - sila muat semula.');
        }

        $card = $hand[$cardIndex];
        $top = end($state['discard']);

        if (! self::isPlayable($card, $top, $state['currentColor'])) {
            throw new \InvalidArgumentException('Kad ini tidak boleh dimainkan sekarang.');
        }

        $isWild = $card['type'] === 'wild' || $card['type'] === 'wild4';
        if ($isWild && ! in_array($chosenColor, self::COLORS, true)) {
            throw new \InvalidArgumentException('Sila pilih warna untuk kad ini.');
        }

        array_splice($hand, $cardIndex, 1);
        $state['hands'][(string) $userId] = $hand;
        $state['discard'][] = $card;
        $state['currentColor'] = $isWild ? $chosenColor : $card['color'];
        $state['calledUno'] = array_values(array_diff($state['calledUno'], [$userId]));

        $state['log'][] = self::label($userId).' main '.$card['color'].' '.$card['value'].'.';
        if (count($state['log']) > 20) {
            $state['log'] = array_slice($state['log'], -20);
        }

        if (empty($state['hands'][(string) $userId])) {
            $state['status'] = 'finished';
            $state['winnerId'] = $userId;
            $state['log'][] = self::label($userId).' menang! 🎉';

            return $state;
        }

        self::advanceTurn($state, $card['type']);

        return $state;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function draw(array $state, int $userId): array
    {
        if ($state['status'] !== 'active') {
            throw new \InvalidArgumentException('Permainan sudah tamat.');
        }
        if (self::currentPlayerId($state) !== $userId) {
            throw new \InvalidArgumentException('Bukan giliran anda.');
        }
        if ($state['drawnThisTurn']) {
            throw new \InvalidArgumentException('Anda sudah tarik kad pusingan ini.');
        }

        $drawn = self::drawFromDeck($state, 1);
        if (empty($drawn)) {
            throw new \InvalidArgumentException('Kad sudah habis.');
        }

        $state['hands'][(string) $userId][] = $drawn[0];
        $state['calledUno'] = array_values(array_diff($state['calledUno'], [$userId]));
        $state['drawnThisTurn'] = true;

        $top = end($state['discard']);
        $canPlayDrawn = self::isPlayable($drawn[0], $top, $state['currentColor']);

        if (! $canPlayDrawn) {
            $state['log'][] = self::label($userId).' tarik kad & tak boleh main.';
            self::advanceTurn($state, 'draw-pass');
        } else {
            $state['log'][] = self::label($userId).' tarik kad.';
        }

        return $state;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function pass(array $state, int $userId): array
    {
        if ($state['status'] !== 'active') {
            throw new \InvalidArgumentException('Permainan sudah tamat.');
        }
        if (self::currentPlayerId($state) !== $userId) {
            throw new \InvalidArgumentException('Bukan giliran anda.');
        }
        if (! $state['drawnThisTurn']) {
            throw new \InvalidArgumentException('Tarik kad dahulu sebelum boleh pass.');
        }

        $state['log'][] = self::label($userId).' pass giliran.';
        self::advanceTurn($state, 'draw-pass');

        return $state;
    }

    public static function callUno(array $state, int $userId): array
    {
        if (count($state['hands'][(string) $userId] ?? []) === 1 && ! in_array($userId, $state['calledUno'], true)) {
            $state['calledUno'][] = $userId;
            $state['log'][] = self::label($userId).' panggil UNO! 📢';
        }

        return $state;
    }

    /**
     * Bina view state khas utk satu pemain - tangan sendiri penuh, tangan
     * orang lain cuma kiraan kad (jangan sekali-kali bocorkan kad org lain).
     */
    public static function viewFor(array $state, int $userId): array
    {
        $handCounts = [];
        foreach ($state['hands'] as $uid => $hand) {
            $handCounts[$uid] = count($hand);
        }

        return [
            'status' => $state['status'],
            'order' => $state['order'],
            'turnIndex' => $state['turnIndex'],
            'currentPlayerId' => $state['status'] === 'active' ? self::currentPlayerId($state) : null,
            'direction' => $state['direction'],
            'currentColor' => $state['currentColor'],
            'discardTop' => end($state['discard']) ?: null,
            'deckCount' => count($state['deck']),
            'drawnThisTurn' => $state['drawnThisTurn'],
            'myHand' => $state['hands'][(string) $userId] ?? [],
            'handCounts' => $handCounts,
            'calledUno' => $state['calledUno'],
            'winnerId' => $state['winnerId'],
            'log' => array_slice($state['log'], -8),
        ];
    }
}
