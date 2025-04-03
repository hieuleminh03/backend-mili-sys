<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FitnessTestThreshold extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fitness_test_id',
        'excellent_threshold',
        'good_threshold',
        'pass_threshold',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'excellent_threshold' => 'decimal:2',
        'good_threshold' => 'decimal:2',
        'pass_threshold' => 'decimal:2',
    ];

    /**
     * Get the fitness test this threshold belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fitnessTest(): BelongsTo
    {
        return $this->belongsTo(FitnessTest::class);
    }

    /**
     * Xác thực rằng các ngưỡng đánh giá phải tuân theo thứ tự đúng
     * Excellent phải tốt hơn Good và Good phải tốt hơn Pass
     *
     * @return bool|array true nếu thỏa mãn, array chứa lỗi nếu không
     */
    public function validateThresholdOrder()
    {
        $errors = [];
        $test = $this->fitnessTest;
        
        if (!$test) {
            return true; // Cannot validate without test
        }
        
        if ($test->higher_is_better) {
            // Highest values should be better (ví dụ mét, lần)
            if ($this->excellent_threshold < $this->good_threshold) {
                $errors[] = "Ngưỡng Giỏi phải cao hơn hoặc bằng ngưỡng Khá";
            }
            
            if ($this->good_threshold < $this->pass_threshold) {
                $errors[] = "Ngưỡng Khá phải cao hơn hoặc bằng ngưỡng Đạt";
            }
        } else {
            // Lowest values should be better (ví dụ giây)
            if ($this->excellent_threshold > $this->good_threshold) {
                $errors[] = "Ngưỡng Giỏi phải thấp hơn hoặc bằng ngưỡng Khá";
            }
            
            if ($this->good_threshold > $this->pass_threshold) {
                $errors[] = "Ngưỡng Khá phải thấp hơn hoặc bằng ngưỡng Đạt";
            }
        }
        
        return empty($errors) ? true : $errors;
    }
}