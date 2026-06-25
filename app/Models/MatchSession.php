<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchSession extends Model
{
    protected $fillable = [
        'user_a_id', 'user_b_id', 'status',
        'user_a_loved', 'user_b_loved',
        'expires_at', 'revealed_at', 'ended_at',
        'blocked_by', 'origin',
    ];

    protected $casts = [
        'user_a_loved' => 'boolean',
        'user_b_loved' => 'boolean',
        'expires_at' => 'datetime',
        'revealed_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function userA()
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    public function userB()
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }

    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // Cari sesi sedia ada (apa-apa status) antara dua user, tak kira A/B.
    public function scopeBetweenUsers($query, int $userIdOne, int $userIdTwo)
    {
        return $query->where(function ($q) use ($userIdOne, $userIdTwo) {
            $q->where(['user_a_id' => $userIdOne, 'user_b_id' => $userIdTwo])
                ->orWhere(['user_a_id' => $userIdTwo, 'user_b_id' => $userIdOne]);
        });
    }

    public function partnerOf(User $user): User
    {
        return $this->user_a_id === $user->id ? $this->userB : $this->userA;
    }

    public function isRevealed(): bool
    {
        return $this->status === 'revealed';
    }

    public function isEnded(): bool
    {
        return $this->status === 'ended';
    }

    public function isBlocked(): bool
    {
        return ! is_null($this->blocked_by);
    }

    public function blockedByUser(User $user): bool
    {
        return $this->blocked_by === $user->id;
    }

    public function hasExpired(): bool
    {
        if ($this->isRevealed() || is_null($this->expires_at)) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    public function lovedBy(User $user): bool
    {
        return $this->user_a_id === $user->id ? $this->user_a_loved : $this->user_b_loved;
    }

    public function bothLoved(): bool
    {
        return $this->user_a_loved && $this->user_b_loved;
    }
}
