<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRoom extends Model
{
    protected $fillable = [
        'game', 'status', 'created_by', 'min_players', 'max_players',
        'state', 'winner_user_id', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'state' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function players()
    {
        return $this->hasMany(GameRoomPlayer::class)->orderBy('seat');
    }

    public function joinedPlayers()
    {
        return $this->players()->where('status', 'joined');
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    // Cari row pemain (atau bot) untuk satu user dalam room ni. Null kalau bukan ahli.
    public function playerFor(?int $userId): ?GameRoomPlayer
    {
        if ($userId === null) {
            return null;
        }

        return $this->players->firstWhere('user_id', $userId);
    }

    public function isMember(int $userId): bool
    {
        return $this->playerFor($userId) !== null;
    }
}
