<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    protected $fillable = ['user_id', 'gender', 'race'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
