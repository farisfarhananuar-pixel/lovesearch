<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MatchSession;
use App\Models\Notification;
use App\Models\QueueEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    // Bila user tekan butang "Cari Jodoh" (boleh sertakan keutamaan umur/semester - optional)
    public function search(Request $request)
    {
        $user = $request->user();
        $user->refreshMonthlyCredits();

        if (QueueEntry::where('user_id', $user->id)->exists()) {
            return redirect()->route('match.waiting');
        }

        if ($user->credits < 1) {
            return redirect()->route('payment.index')
                ->with('error', 'Credit anda dah habis. Beli credit untuk teruskan carian jodoh ya!');
        }

        $pref = $request->validate([
            'pref_min_age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'pref_max_age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'pref_min_semester' => ['nullable', 'integer', 'min:1', 'max:20'],
            'pref_max_semester' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        // Susun semula kalau min > max (supaya tak silap input).
        if (! empty($pref['pref_min_age']) && ! empty($pref['pref_max_age']) && $pref['pref_min_age'] > $pref['pref_max_age']) {
            [$pref['pref_min_age'], $pref['pref_max_age']] = [$pref['pref_max_age'], $pref['pref_min_age']];
        }
        if (! empty($pref['pref_min_semester']) && ! empty($pref['pref_max_semester']) && $pref['pref_min_semester'] > $pref['pref_max_semester']) {
            [$pref['pref_min_semester'], $pref['pref_max_semester']] = [$pref['pref_max_semester'], $pref['pref_min_semester']];
        }

        // Kalau user tekan "teruskan tanpa filter" pada popup, kita buang terus keutamaan dia.
        $ignorePref = $request->boolean('ignore_pref');
        if ($ignorePref) {
            $pref = [
                'pref_min_age' => null,
                'pref_max_age' => null,
                'pref_min_semester' => null,
                'pref_max_semester' => null,
            ];
        }

        $hasPref = ! empty($pref['pref_min_age']) || ! empty($pref['pref_max_age'])
            || ! empty($pref['pref_min_semester']) || ! empty($pref['pref_max_semester']);

        // Kalau user set filter (dan belum confirm nak ignore), semak dulu ada ke tak calon yang
        // sepadan SEBELUM kita potong credit / masukkan dia dalam queue. Kalau tak ada, jangan terus
        // proceed - bagi balik flag supaya frontend papar popup tanya nak teruskan rawak atau tunggu ikut filter.
        if ($hasPref && ! $ignorePref && ! $request->boolean('confirmed_wait')) {
            $candidateExists = $this->buildCandidateQuery($user, $pref)->exists();

            if (! $candidateExists) {
                return redirect()->route('dashboard')->with('no_filter_match', true)->withInput();
            }
        }

        $matchSessionId = DB::transaction(function () use ($user, $pref) {
            $query = $this->buildCandidateQuery($user, $pref);

            // ...dan calon yang dah set keutamaan dia sendiri pula kena padan dengan SAYA (mutual).
            $query->where(function ($q) use ($user) {
                $q->whereNull('pref_min_age')->orWhere('pref_min_age', '<=', $user->age ?? 18);
            })->where(function ($q) use ($user) {
                $q->whereNull('pref_max_age')->orWhere('pref_max_age', '>=', $user->age ?? 999);
            })->where(function ($q) use ($user) {
                $q->whereNull('pref_min_semester')->orWhere('pref_min_semester', '<=', $user->semester ?? 1);
            })->where(function ($q) use ($user) {
                $q->whereNull('pref_max_semester')->orWhere('pref_max_semester', '>=', $user->semester ?? 999);
            });

            $candidate = $query->orderBy('created_at')->lockForUpdate()->first();

            // Guna 1 credit setiap kali tekan "Cari Jodoh"
            $user->decrement('credits');

            if ($candidate) {
                $candidate->delete();

                $match = MatchSession::create([
                    'user_a_id' => $candidate->user_id,
                    'user_b_id' => $user->id,
                    'status' => 'active',
                    'origin' => 'random',
                    'expires_at' => now()->addMinutes(2),
                ]);

                return $match->id;
            }

            QueueEntry::create([
                'user_id' => $user->id,
                'gender' => $user->gender,
                'race' => $user->race,
                'age' => $user->age,
                'semester' => $user->semester,
                'pref_min_age' => $pref['pref_min_age'] ?? null,
                'pref_max_age' => $pref['pref_max_age'] ?? null,
                'pref_min_semester' => $pref['pref_min_semester'] ?? null,
                'pref_max_semester' => $pref['pref_max_semester'] ?? null,
            ]);

            return null;
        });

        if ($matchSessionId) {
            return redirect()->route('match.show', $matchSessionId);
        }

        return redirect()->route('match.waiting');
    }

    public function waiting(Request $request)
    {
        $user = $request->user();

        if (! QueueEntry::where('user_id', $user->id)->exists()) {
            return redirect()->route('dashboard');
        }

        return view('match.waiting');
    }

    // Polling - cek kalau dah ada org match dengan kita semasa kita tunggu.
    public function waitingPoll(Request $request)
    {
        $user = $request->user();

        $match = MatchSession::where(function ($q) use ($user) {
                $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id);
            })
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($match) {
            return response()->json(['matched' => true, 'match_id' => $match->id]);
        }

        return response()->json(['matched' => false]);
    }

    public function cancelWaiting(Request $request)
    {
        $user = $request->user();
        $entry = QueueEntry::where('user_id', $user->id)->first();

        if ($entry) {
            $entry->delete();
            // Refund credit sebab tak jadi match.
            $user->increment('credits');
        }

        return redirect()->route('dashboard')->with('status', 'Carian dibatalkan. Credit anda dikembalikan.');
    }

    // Query asas untuk cari calon dalam queue ikut keutamaan SAYA (extract supaya boleh
    // dipakai untuk semakan dulu (exists check) dan masa proses match sebenar - elak duplicate code.
    private function buildCandidateQuery($user, array $pref)
    {
        $query = QueueEntry::where('gender', $user->oppositeGender())
            ->where('race', $user->race)
            ->where('user_id', '!=', $user->id);

        // Calon kena ikut keutamaan SAYA (kalau saya set umur/semester yang dimahukan).
        if (! empty($pref['pref_min_age'])) {
            $query->where(function ($q) use ($pref) {
                $q->whereNull('age')->orWhere('age', '>=', $pref['pref_min_age']);
            });
        }
        if (! empty($pref['pref_max_age'])) {
            $query->where(function ($q) use ($pref) {
                $q->whereNull('age')->orWhere('age', '<=', $pref['pref_max_age']);
            });
        }
        if (! empty($pref['pref_min_semester'])) {
            $query->where(function ($q) use ($pref) {
                $q->whereNull('semester')->orWhere('semester', '>=', $pref['pref_min_semester']);
            });
        }
        if (! empty($pref['pref_max_semester'])) {
            $query->where(function ($q) use ($pref) {
                $q->whereNull('semester')->orWhere('semester', '<=', $pref['pref_max_semester']);
            });
        }

        return $query;
    }

    private function authorizeMatch(MatchSession $match, $user)
    {
        abort_unless($match->user_a_id === $user->id || $match->user_b_id === $user->id, 403);
    }

    private function expireIfNeeded(MatchSession $match): MatchSession
    {
        if ($match->status === 'active' && $match->hasExpired()) {
            $match->update(['status' => 'ended', 'ended_at' => now()]);
        }

        return $match->fresh();
    }

    public function show(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);
        $match = $this->expireIfNeeded($match);

        $partner = $match->partnerOf($user);
        $messages = $match->messages()->get();

        return view('match.chat', [
            'match' => $match,
            'user' => $user,
            'partner' => $partner,
            'messages' => $messages,
            'revealed' => $match->isRevealed(),
        ]);
    }

    public function sendMessage(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);
        $match = $this->expireIfNeeded($match);

        $request->validate(['body' => ['required', 'string', 'max:500']]);

        if ($match->status === 'ended') {
            return response()->json(['error' => 'Sesi sembang ini sudah tamat.'], 422);
        }

        if ($match->isBlocked()) {
            return response()->json(['error' => 'Sembang ini sedang disekat.', 'blocked' => true], 422);
        }

        $message = Message::create([
            'match_session_id' => $match->id,
            'sender_id' => $user->id,
            'body' => $request->input('body'),
            'created_at' => now(),
        ]);

        // Bagi notification kat partner kalau ni sembang kawan kekal (revealed) - untuk sembang
        // misteri/anonymous yang masih dalam 2 minit tu tak perlu sebab dia memang sedang live chat.
        if ($match->status === 'revealed') {
            $partner = $match->partnerOf($user);

            Notification::send(
                $partner->id,
                'message',
                $user->displayName().' menghantar mesej',
                str($request->input('body'))->limit(60),
                route('match.show', $match->id)
            );
        }

        return response()->json(['ok' => true, 'message_id' => $message->id]);
    }

    public function poll(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);
        $match = $this->expireIfNeeded($match);

        $afterId = (int) $request->query('after_id', 0);

        $messages = $match->messages()
            ->where('id', '>', $afterId)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'mine' => $m->sender_id === $user->id,
                'body' => $m->body,
                'time' => $m->created_at->format('H:i'),
            ]);

        $partner = $match->partnerOf($user);

        return response()->json([
            'status' => $match->status,
            'revealed' => $match->isRevealed(),
            'partner_name' => $match->isRevealed() ? $partner->displayName() : null,
            'partner_photo' => $match->isRevealed() ? $partner->profile_photo : null,
            'partner_avatar_fallback' => $partner->avatarFallback(),
            'i_loved' => $match->lovedBy($user),
            'partner_loved' => $match->lovedBy($partner),
            'seconds_left' => $match->isRevealed() ? null : max(0, now()->diffInSeconds($match->expires_at, false)),
            'blocked' => $match->isBlocked(),
            'blocked_by_me' => $match->blockedByUser($user),
            'messages' => $messages,
        ]);
    }

    // Tekan butang "Suka" / Love - kalau kedua-dua tekan, sesi jadi "revealed"
    // (= jadi kawan kekal, boleh sembang tanpa had & akan muncul dalam senarai Kawan).
    public function love(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);
        $match = $this->expireIfNeeded($match);

        if ($match->status === 'ended') {
            return response()->json(['error' => 'Sesi sudah tamat.'], 422);
        }

        if ($match->user_a_id === $user->id) {
            $match->user_a_loved = true;
        } else {
            $match->user_b_loved = true;
        }

        if ($match->bothLoved() && $match->status !== 'revealed') {
            $match->status = 'revealed';
            $match->revealed_at = now();
        }

        $match->save();

        return response()->json(['ok' => true, 'revealed' => $match->isRevealed()]);
    }

    // "Tamatkan sembang" - hanya sah utk sesi yang masih anonymous/aktif (skip stranger).
    // Sesi yang dah "revealed" (kawan) tak boleh ditamatkan macam ni - guna Block/Buang Kawan.
    public function leave(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);

        if ($match->status === 'revealed') {
            return back()->with('error', 'Anda berkawan dengan orang ini. Guna fungsi Sekat atau Buang Kawan kalau perlu.');
        }

        if ($match->status !== 'ended') {
            $match->update(['status' => 'ended', 'ended_at' => now()]);
        }

        return redirect()->route('dashboard')->with('status', 'Sesi sembang ditamatkan.');
    }

    // Sekat kawan - sembang dibekukan sehingga dibuka semula oleh org yang menyekat.
    public function block(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);

        abort_unless($match->status === 'revealed', 403);

        if (! $match->blocked_by) {
            $match->update(['blocked_by' => $user->id]);
        }

        return back()->with('status', 'Sembang dengan kawan ini telah disekat. Anda boleh buka sekatan bila-bila masa.');
    }

    public function unblock(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);

        abort_unless($match->blocked_by === $user->id, 403);

        $match->update(['blocked_by' => null]);

        return back()->with('status', 'Sekatan dibuka. Anda boleh sembang semula sekarang.');
    }

    // Buang kawan secara kekal - lain dengan Block (sementara), ini hilangkan terus dari senarai Kawan.
    public function unfriend(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);

        abort_unless($match->status === 'revealed', 403);

        $match->update(['status' => 'ended', 'ended_at' => now()]);

        return redirect()->route('friends.index')->with('status', 'Kawan ini telah dibuang dari senarai.');
    }
}
