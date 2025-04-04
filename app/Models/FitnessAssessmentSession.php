<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FitnessAssessmentSession extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể gán hàng loạt
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'week_start_date',
        'week_end_date',
        'notes',
    ];

    /**
     * Các thuộc tính cần cast
     *
     * @var array
     */
    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];

    /**
     * Lấy tất cả kết quả đánh giá của học viên trong phiên này
     */
    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentFitnessRecord::class, 'assessment_session_id');
    }

    /**
     * Lấy hoặc tạo phiên đánh giá cho tuần hiện tại
     *
     * @return self Phiên đánh giá của tuần hiện tại
     */
    public static function getCurrentWeekSession(): self
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek()->startOfDay(); // Thứ 2
        $endOfWeek = $now->copy()->endOfWeek()->endOfDay();     // Chủ nhật

        // Sử dụng so sánh chính xác ngày tháng để đảm bảo nhất quán
        $startDate = $startOfWeek->format('Y-m-d');
        $endDate = $endOfWeek->format('Y-m-d');

        $session = self::whereDate('week_start_date', $startDate)
            ->whereDate('week_end_date', $endDate)
            ->first();

        \Log::info('getCurrentWeekSession query', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sessionFound' => (bool) $session,
        ]);

        if (! $session) {
            $weekNumber = $now->weekOfYear;
            $monthName = $now->translatedFormat('F');
            $year = $now->year;

            $session = self::create([
                'name' => "Tuần $weekNumber - Tháng $monthName/$year",
                'week_start_date' => $startDate,
                'week_end_date' => $endDate,
            ]);
        }

        return $session;
    }

    /**
     * Kiểm tra xem phiên đánh giá có phải là tuần hiện tại không
     *
     * @return bool True nếu là phiên của tuần hiện tại
     */
    public function isCurrentWeek(): bool
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek()->startOfDay(); // Thứ 2
        $endOfWeek = $now->copy()->endOfWeek()->endOfDay();       // Chủ nhật

        $sessionStart = $this->week_start_date->startOfDay();
        $sessionEnd = $this->week_end_date->endOfDay();

        return $sessionStart->format('Y-m-d') === $startOfWeek->format('Y-m-d') &&
            $sessionEnd->format('Y-m-d') === $endOfWeek->format('Y-m-d');
    }
}
