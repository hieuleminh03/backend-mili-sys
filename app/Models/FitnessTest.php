<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FitnessTest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'unit',
        'higher_is_better',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'higher_is_better' => 'boolean',
    ];

    /**
     * Get the thresholds for this fitness test.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function thresholds(): HasOne
    {
        return $this->hasOne(FitnessTestThreshold::class);
    }

    /**
     * Get the student records for this fitness test.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentFitnessRecord::class);
    }

    /**
     * Xác định xếp loại dựa trên performance và thresholds
     *
     * @param float $performance kết quả đạt được
     * @return string xếp loại ('excellent', 'good', 'pass' hoặc 'fail')
     */
    public function determineRating(float $performance): string
    {
        if (!$this->thresholds) {
            return 'fail';
        }

        if ($this->higher_is_better) {
            // Với các chỉ số cao hơn là tốt hơn (như mét đạt được)
            if ($performance >= $this->thresholds->excellent_threshold) {
                return 'excellent';
            } elseif ($performance >= $this->thresholds->good_threshold) {
                return 'good';
            } elseif ($performance >= $this->thresholds->pass_threshold) {
                return 'pass';
            }
        } else {
            // Với các chỉ số thấp hơn là tốt hơn (như giây chạy)
            if ($performance <= $this->thresholds->excellent_threshold) {
                return 'excellent';
            } elseif ($performance <= $this->thresholds->good_threshold) {
                return 'good';
            } elseif ($performance <= $this->thresholds->pass_threshold) {
                return 'pass';
            }
        }

        return 'fail';
    }
}