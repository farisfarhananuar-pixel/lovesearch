<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $timestamps = false;

    protected $fillable = ['match_session_id', 'sender_id', 'body', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function matchSession()
    {
        return $this->belongsTo(MatchSession::class);
    }
}
