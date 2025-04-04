<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FitnessTestThreshold extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể gán hàng loạt
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
     * Các thuộc tính cần cast
     *
     * @var array
     */
    protected $casts = [
        'excellent_threshold' => 'decimal:2',
        'good_threshold' => 'decimal:2',
        'pass_threshold' => 'decimal:2',
    ];

    /**
     * Lấy bài kiểm tra thể lực mà ngưỡng này thuộc về
     */
    public function fitnessTest(): BelongsTo
    {
        return $this->belongsTo(FitnessTest::class);
    }

    /**
     * Kiểm tra tính hợp lệ của thứ tự các ngưỡng đánh giá
     * Excellent > Good > Pass (hoặc ngược lại tùy thuộc vào higher_is_better)
     *
     * @return bool|array True nếu hợp lệ, array chứa thông báo lỗi nếu không
     */
    public function validateThresholdOrder()
    {
        $errors = [];
        $test = $this->fitnessTest;

        if (! $test) {
            return true; // Không thể validate khi chưa có bài kiểm tra
        }

        if ($test->higher_is_better) {
            // Giá trị cao hơn là tốt hơn (chạy xa, bơi xa)
            if ($this->excellent_threshold < $this->good_threshold) {
                $errors[] = 'Ngưỡng Giỏi phải cao hơn hoặc bằng ngưỡng Khá';
            }

            if ($this->good_threshold < $this->pass_threshold) {
                $errors[] = 'Ngưỡng Khá phải cao hơn hoặc bằng ngưỡng Đạt';
            }
        } else {
            // Giá trị thấp hơn là tốt hơn (thời gian chạy)
            if ($this->excellent_threshold > $this->good_threshold) {
                $errors[] = 'Ngưỡng Giỏi phải thấp hơn hoặc bằng ngưỡng Khá';
            }

            if ($this->good_threshold > $this->pass_threshold) {
                $errors[] = 'Ngưỡng Khá phải thấp hơn hoặc bằng ngưỡng Đạt';
            }
        }

        return empty($errors) ? true : $errors;
    }
}
