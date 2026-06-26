<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRoomPlayer extends Model
{
    protected $fillable = [
        'game_room_id', 'user_id', 'is_bot', 'seat', 'status', 'invited_by',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isJoined(): bool
    {
        return $this->status === 'joined';
    }
}
