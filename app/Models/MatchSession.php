<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchSession extends Model
{
    protected $fillable = [
        'user_a_id', 'user_b_id', 'status',
        'user_a_loved', 'user_b_loved',
        'expires_at', 'revealed_at', 'ended_at',
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

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
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

    public function hasExpired(): bool
    {
        return ! $this->isRevealed() && now()->greaterThanOrEqualTo($this->expires_at);
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
