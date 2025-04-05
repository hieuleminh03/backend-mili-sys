<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyAllowance extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể được gán hàng loạt
     */
    protected $fillable = [
        'user_id',
        'month',
        'year',
        'amount',
        'received',
        'received_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'amount' => 'decimal:2',
        'received' => 'boolean',
        'received_at' => 'datetime',
    ];

    /**
     * Get the student for this allowance
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}