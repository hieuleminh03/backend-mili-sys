<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilitaryEquipmentType extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể được gán hàng loạt
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the distributions for this equipment type
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(YearlyEquipmentDistribution::class, 'equipment_type_id');
    }
}