<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MatchSession;
use App\Models\QueueEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    // Bila user tekan butang "Cari Jodoh"
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

        $matchSessionId = DB::transaction(function () use ($user) {
            // Cari calon yang sepadan: jantina lawan, bangsa sama, bukan diri sendiri.
            $candidate = QueueEntry::where('gender', $user->oppositeGender())
                ->where('race', $user->race)
                ->where('user_id', '!=', $user->id)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            // Guna 1 credit setiap kali tekan "Cari Jodoh"
            $user->decrement('credits');

            if ($candidate) {
                $candidate->delete();

                $match = MatchSession::create([
                    'user_a_id' => $candidate->user_id,
                    'user_b_id' => $user->id,
                    'status' => 'active',
                    'expires_at' => now()->addMinutes(2),
                ]);

                return $match->id;
            }

            QueueEntry::create([
                'user_id' => $user->id,
                'gender' => $user->gender,
                'race' => $user->race,
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

        $message = Message::create([
            'match_session_id' => $match->id,
            'sender_id' => $user->id,
            'body' => $request->input('body'),
            'created_at' => now(),
        ]);

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
            'partner_name' => $match->isRevealed() ? $partner->full_name : null,
            'i_loved' => $match->lovedBy($user),
            'partner_loved' => $match->lovedBy($partner),
            'seconds_left' => $match->isRevealed() ? null : max(0, now()->diffInSeconds($match->expires_at, false)),
            'messages' => $messages,
        ]);
    }

    // Tekan butang "Suka" / Love
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

    public function leave(Request $request, MatchSession $match)
    {
        $user = $request->user();
        $this->authorizeMatch($match, $user);

        if ($match->status !== 'ended') {
            $match->update(['status' => 'ended', 'ended_at' => now()]);
        }

        return redirect()->route('dashboard')->with('status', 'Sesi sembang ditamatkan.');
    }
}
