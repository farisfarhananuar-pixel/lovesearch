<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'payer_full_name', 'package_credits',
        'package_price', 'receipt_path', 'receipt_data', 'status',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Resit kini disimpan terus dalam DB (base64). Fallback ke storage lama
    // (kalau ada rekod lama yang masih guna receipt_path) supaya tak rosak.
    public function receiptSrc(): ?string
    {
        if ($this->receipt_data) {
            return $this->receipt_data;
        }

        if ($this->receipt_path) {
            return asset('storage/'.$this->receipt_path);
        }

        return null;
    }
}
