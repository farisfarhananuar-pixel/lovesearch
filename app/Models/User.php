<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'display_name',
        'profile_photo',
        'phone',
        'password',
        'gender',
        'race',
        'age',
        'semester',
        'credits',
        'last_free_topup_month',
        'age_confirmed',
        'friend_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'age_confirmed' => 'boolean',
        'is_blocked' => 'boolean',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Nama yang dipaparkan dalam chat/senarai kawan - guna display_name kalau
    // user dah set, kalau tak guna full_name (nama pendaftaran).
    public function displayName(): string
    {
        return $this->display_name ?: $this->full_name;
    }

    // Emoji avatar lalai (ikut jantina) bila user belum upload gambar profil.
    public function avatarFallback(): string
    {
        return $this->gender === 'lelaki' ? '👦' : '👧';
    }

    // Padanan lawan jantina utk match (lelaki <-> perempuan)
    public function oppositeGender(): string
    {
        return $this->gender === 'lelaki' ? 'perempuan' : 'lelaki';
    }

    // Pastikan credit free bulanan di-topup kalau dah masuk bulan baru.
    public function refreshMonthlyCredits(): void
    {
        $currentMonth = now()->format('Y-m');

        if ($this->last_free_topup_month !== $currentMonth) {
            $this->increment('credits', 5);
            $this->last_free_topup_month = $currentMonth;
            $this->save();
        }
    }

    public function sentFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }

    // Semua sesi (match_sessions) yang user ni terlibat, tak kira sebagai A atau B.
    public function matchSessions()
    {
        return MatchSession::where('user_a_id', $this->id)->orWhere('user_b_id', $this->id);
    }

    // Senarai kawan (sesi yang dah "revealed" - permanent, boleh chat tanpa had).
    public function friendSessions()
    {
        return $this->matchSessions()->where('status', 'revealed');
    }
}
