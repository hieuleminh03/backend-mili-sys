<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class FitnessTest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Các thuộc tính có thể gán hàng loạt
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'unit',
        'higher_is_better',
    ];

    /**
     * Các thuộc tính cần cast
     *
     * @var array
     */
    protected $casts = [
        'higher_is_better' => 'boolean',
    ];

    /**
     * Lấy ngưỡng đánh giá của bài kiểm tra
     */
    public function thresholds(): HasOne
    {
        return $this->hasOne(FitnessTestThreshold::class);
    }

    /**
     * Lấy kết quả đánh giá của học viên cho bài kiểm tra này
     */
    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentFitnessRecord::class);
    }

    /**
     * Xác định xếp loại dựa trên kết quả và ngưỡng đánh giá
     *
     * @param  float  $performance  Kết quả đạt được
     * @return string Xếp loại ('excellent', 'good', 'pass' hoặc 'fail')
     */
    public function determineRating(float $performance): string
    {
        if (! $this->thresholds) {
            return 'fail';
        }

        if ($this->higher_is_better) {
            // Với các chỉ số cao hơn là tốt hơn (như chạy xa, bơi xa)
            if ($performance >= $this->thresholds->excellent_threshold) {
                return 'excellent';
            } elseif ($performance >= $this->thresholds->good_threshold) {
                return 'good';
            } elseif ($performance >= $this->thresholds->pass_threshold) {
                return 'pass';
            }
        } else {
            // Với các chỉ số thấp hơn là tốt hơn (như thời gian chạy)
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
