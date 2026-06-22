<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone',
        'password',
        'gender',
        'race',
        'credits',
        'last_free_topup_month',
        'age_confirmed',
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
}
