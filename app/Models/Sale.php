<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'amount',
        'description',
        'date',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
