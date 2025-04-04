<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFitnessRecord extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể gán hàng loạt
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'manager_id',
        'fitness_test_id',
        'assessment_session_id',
        'performance',
        'rating',
        'notes',
    ];

    /**
     * Các thuộc tính cần cast
     *
     * @var array
     */
    protected $casts = [
        'performance' => 'decimal:2',
    ];

    /**
     * Lấy thông tin học viên sở hữu bản ghi đánh giá này
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lấy thông tin quản lý đã tạo bản ghi đánh giá
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Lấy thông tin bài kiểm tra thể lực của bản ghi
     */
    public function fitnessTest(): BelongsTo
    {
        return $this->belongsTo(FitnessTest::class, 'fitness_test_id');
    }

    /**
     * Lấy thông tin phiên đánh giá của bản ghi
     */
    public function assessmentSession(): BelongsTo
    {
        return $this->belongsTo(FitnessAssessmentSession::class, 'assessment_session_id');
    }

    /**
     * Tính toán xếp loại dựa trên kết quả và ngưỡng đánh giá
     * Cần gọi trước khi lưu bản ghi
     */
    public function calculateRating(): void
    {
        if ($this->fitnessTest && $this->fitnessTest->thresholds) {
            $this->rating = $this->fitnessTest->determineRating($this->performance);
        }
    }
}
