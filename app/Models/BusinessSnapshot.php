<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSnapshot extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessSnapshotsFactory> */
    use HasFactory;

    protected $table = 'business_snapshots';

    protected $fillable = [
        'business_id',
        'month',
        'total_sales',
        'total_expenses',
        'net_profit',
        'transaction_count',
    ];

    protected $casts = [
        'total_sales' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'transaction_count' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
