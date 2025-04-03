<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class FitnessAssessmentSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];

    /**
     * Get all student fitness records for this assessment session.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentFitnessRecord::class, 'assessment_session_id');
    }

    /**
     * Tạo một session mới cho tuần hiện tại nếu chưa tồn tại
     * 
     * @return self session được tạo hoặc lấy từ DB
     */
    public static function getCurrentWeekSession(): self
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek()->startOfDay(); // Monday
        $endOfWeek = $now->copy()->endOfWeek()->endOfDay();     // Sunday
        
        // Use exact date comparison with formatted dates to ensure consistency
        $startDate = $startOfWeek->format('Y-m-d');
        $endDate = $endOfWeek->format('Y-m-d');
        
        $session = self::whereDate('week_start_date', $startDate)
                      ->whereDate('week_end_date', $endDate)
                      ->first();

        \Log::info('getCurrentWeekSession query', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sessionFound' => !!$session,
        ]);
                      
        if (!$session) {
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
     * Check xem session có phải là tuần hiện tại không
     * 
     * @return bool
     */
    public function isCurrentWeek(): bool
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek()->startOfDay(); // Monday
        $endOfWeek = $now->copy()->endOfWeek()->endOfDay();       // Sunday
        
        $sessionStart = $this->week_start_date->startOfDay();
        $sessionEnd = $this->week_end_date->endOfDay();
        
        return $sessionStart->format('Y-m-d') === $startOfWeek->format('Y-m-d') && 
               $sessionEnd->format('Y-m-d') === $endOfWeek->format('Y-m-d');
    }
}