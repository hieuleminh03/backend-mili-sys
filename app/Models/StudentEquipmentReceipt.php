<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEquipmentReceipt extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể được gán hàng loạt
     */
    protected $fillable = [
        'user_id',
        'distribution_id',
        'received',
        'received_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'received' => 'boolean',
        'received_at' => 'datetime',
    ];

    /**
     * Get the student for this receipt
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the distribution for this receipt
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(YearlyEquipmentDistribution::class, 'distribution_id');
    }
}