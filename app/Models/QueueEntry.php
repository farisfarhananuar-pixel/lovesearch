<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    protected $fillable = [
        'user_id', 'gender', 'race', 'age', 'semester',
        'pref_min_age', 'pref_max_age', 'pref_min_semester', 'pref_max_semester',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
