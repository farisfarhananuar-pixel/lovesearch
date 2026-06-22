<?php

namespace App\Http\Controllers;

use App\Models\MatchSession;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->refreshMonthlyCredits();

        // Kalau ada match aktif/revealed yang belum berakhir, terus bawa ke chat.
        $activeMatch = MatchSession::where(function ($q) use ($user) {
                $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id);
            })
            ->whereIn('status', ['active', 'revealed'])
            ->latest()
            ->first();

        if ($activeMatch && $activeMatch->hasExpired()) {
            $activeMatch->update(['status' => 'ended', 'ended_at' => now()]);
            $activeMatch = null;
        }

        if ($activeMatch) {
            return redirect()->route('match.show', $activeMatch->id);
        }

        $inQueue = \App\Models\QueueEntry::where('user_id', $user->id)->exists();

        if ($inQueue) {
            return redirect()->route('match.waiting');
        }

        return view('dashboard.index', compact('user'));
    }
}
