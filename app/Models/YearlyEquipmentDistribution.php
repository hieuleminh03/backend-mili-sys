<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YearlyEquipmentDistribution extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể được gán hàng loạt
     */
    protected $fillable = [
        'year',
        'equipment_type_id',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'year' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * Get the equipment type for this distribution
     */
    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(MilitaryEquipmentType::class, 'equipment_type_id');
    }

    /**
     * Get the student receipts for this distribution
     */
    public function studentReceipts(): HasMany
    {
        return $this->hasMany(StudentEquipmentReceipt::class, 'distribution_id');
    }
}