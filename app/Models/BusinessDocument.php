<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDocument extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'type',
        'file_path',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
