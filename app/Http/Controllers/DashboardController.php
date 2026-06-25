<?php

namespace App\Http\Controllers;

use App\Models\MatchSession;
use App\Models\QueueEntry;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->refreshMonthlyCredits();

        // Hanya sesi "active" (sembang misteri yang masih berdetik 2 minit) yang
        // memaksa redirect terus ke chat. Sesi "revealed" (kawan) tak lagi
        // memaksa redirect - ia muncul dalam senarai Kawan dan boleh dibuka
        // bila-bila masa mereka mahu.
        $activeMatch = MatchSession::where(function ($q) use ($user) {
                $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id);
            })
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($activeMatch && $activeMatch->hasExpired()) {
            $activeMatch->update(['status' => 'ended', 'ended_at' => now()]);
            $activeMatch = null;
        }

        if ($activeMatch) {
            return redirect()->route('match.show', $activeMatch->id);
        }

        $inQueue = QueueEntry::where('user_id', $user->id)->exists();

        if ($inQueue) {
            return redirect()->route('match.waiting');
        }

        // Pratonton ringkas senarai kawan (3 terbaru) - senarai penuh ada di /friends.
        $friendPreview = $user->friendSessions()
            ->with(['userA', 'userB', 'lastMessage'])
            ->get()
            ->map(function (MatchSession $m) use ($user) {
                $partner = $m->partnerOf($user);

                return [
                    'match_id' => $m->id,
                    'partner' => $partner,
                    'last_message' => $m->lastMessage,
                    'sort_time' => $m->lastMessage?->created_at ?? $m->revealed_at,
                    'blocked' => $m->isBlocked(),
                ];
            })
            ->sortByDesc('sort_time')
            ->take(3)
            ->values();

        $pendingRequestsCount = $user->receivedFriendRequests()->where('status', 'pending')->count();

        // Riwayat padanan misteri lepas (yang dah tamat tanpa jadi kawan) - sapa je yang pernah disembang.
        $matchHistory = MatchSession::where(function ($q) use ($user) {
                $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id);
            })
            ->where('status', 'ended')
            ->latest('ended_at')
            ->take(20)
            ->get()
            ->map(function ($m) use ($user) {
                $partner = $m->partnerOf($user);

                return [
                    'id' => $m->id,
                    'name' => $m->isRevealed() ? $partner->displayName() : 'Misteri (tidak terdedah)',
                    'revealed' => $m->isRevealed(),
                    'ended_at' => $m->ended_at,
                ];
            });

        return view('dashboard.index', compact('user', 'matchHistory', 'friendPreview', 'pendingRequestsCount'));
    }
}
