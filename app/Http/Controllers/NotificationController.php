<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->take(50)->get();

        // Bila user buka page ni, anggap semua dah dibaca.
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    // Dipanggil berkala (polling) dari semua page untuk update badge bell icon
    // tanpa kena reload - sama macam pattern waiting.poll / match.poll yang sedia ada.
    public function poll(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotificationsCount(),
            'latest' => $user->notifications()->take(5)->get()->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'link' => $n->link,
                'read' => $n->isRead(),
                'time' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return $notification->link
            ? redirect($notification->link)
            : back();
    }

    public function markAllRead(Request $request)
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
