<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use App\Models\MatchSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendController extends Controller
{
    // Senarai kawan (sesi "revealed") + permintaan kawan yang menunggu.
    public function index(Request $request)
    {
        $user = $request->user();

        $friends = $user->friendSessions()
            ->with(['userA', 'userB', 'lastMessage', 'blocker'])
            ->get()
            ->map(function (MatchSession $m) use ($user) {
                $partner = $m->partnerOf($user);

                return [
                    'match_id' => $m->id,
                    'partner' => $partner,
                    'last_message' => $m->lastMessage,
                    'sort_time' => $m->lastMessage?->created_at ?? $m->revealed_at,
                    'blocked' => $m->isBlocked(),
                    'blocked_by_me' => $m->blocked_by === $user->id,
                ];
            })
            ->sortByDesc('sort_time')
            ->values();

        $pendingReceived = $user->receivedFriendRequests()
            ->where('status', 'pending')->with('sender')->latest()->get();

        $pendingSent = $user->sentFriendRequests()
            ->where('status', 'pending')->with('receiver')->latest()->get();

        return view('friends.index', compact('user', 'friends', 'pendingReceived', 'pendingSent'));
    }

    public function searchForm()
    {
        return view('friends.search', ['found' => null, 'status' => null, 'code' => '']);
    }

    public function search(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:10']]);

        $me = $request->user();
        $code = strtoupper(trim($request->input('code')));
        $found = User::where('friend_code', $code)->first();

        $status = null;

        if ($found) {
            if ($found->id === $me->id) {
                $status = 'self';
            } elseif (MatchSession::betweenUsers($me->id, $found->id)->whereIn('status', ['active', 'revealed'])->exists()) {
                $status = 'already_connected';
            } elseif (FriendRequest::where('sender_id', $me->id)->where('receiver_id', $found->id)->where('status', 'pending')->exists()) {
                $status = 'pending_sent';
            } elseif (FriendRequest::where('sender_id', $found->id)->where('receiver_id', $me->id)->where('status', 'pending')->exists()) {
                $status = 'pending_received';
            } else {
                $status = 'found';
            }
        } else {
            $status = 'not_found';
        }

        return view('friends.search', compact('found', 'status', 'code'));
    }

    public function sendRequest(Request $request, User $targetUser)
    {
        $me = $request->user();

        abort_if($targetUser->id === $me->id, 403);

        $alreadyConnected = MatchSession::betweenUsers($me->id, $targetUser->id)
            ->whereIn('status', ['active', 'revealed'])->exists();

        $existingPending = FriendRequest::where(function ($q) use ($me, $targetUser) {
            $q->where('sender_id', $me->id)->where('receiver_id', $targetUser->id);
        })->orWhere(function ($q) use ($me, $targetUser) {
            $q->where('sender_id', $targetUser->id)->where('receiver_id', $me->id);
        })->where('status', 'pending')->exists();

        if ($alreadyConnected || $existingPending) {
            return back()->with('error', 'Permintaan tidak boleh dihantar - anda mungkin sudah berkawan atau ada permintaan yang masih menunggu.');
        }

        FriendRequest::create([
            'sender_id' => $me->id,
            'receiver_id' => $targetUser->id,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Permintaan kawan dihantar kepada '.$targetUser->displayName().' 🎉');
    }

    public function cancelSentRequest(Request $request, FriendRequest $friendRequest)
    {
        abort_unless($friendRequest->sender_id === $request->user()->id, 403);

        if ($friendRequest->isPending()) {
            $friendRequest->delete();
        }

        return back()->with('status', 'Permintaan kawan dibatalkan.');
    }

    public function accept(Request $request, FriendRequest $friendRequest)
    {
        $user = $request->user();
        abort_unless($friendRequest->receiver_id === $user->id, 403);
        abort_unless($friendRequest->isPending(), 422);

        $matchId = DB::transaction(function () use ($friendRequest) {
            $friendRequest->update(['status' => 'accepted']);

            $match = MatchSession::create([
                'user_a_id' => $friendRequest->sender_id,
                'user_b_id' => $friendRequest->receiver_id,
                'status' => 'revealed',
                'user_a_loved' => true,
                'user_b_loved' => true,
                'revealed_at' => now(),
                'origin' => 'friend_request',
            ]);

            return $match->id;
        });

        return redirect()->route('match.show', $matchId)->with('status', 'Anda kini berkawan! 🎉 Boleh sembang bila-bila masa.');
    }

    public function decline(Request $request, FriendRequest $friendRequest)
    {
        abort_unless($friendRequest->receiver_id === $request->user()->id, 403);

        $friendRequest->update(['status' => 'declined']);

        return back()->with('status', 'Permintaan kawan ditolak.');
    }
}
