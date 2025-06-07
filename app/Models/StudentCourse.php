<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCourse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'midterm_grade',
        'final_grade',
        'total_grade',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'midterm_grade' => 'decimal:2',
        'final_grade'   => 'decimal:2',
        'total_grade'   => 'decimal:2',
    ];

    /**
     * Get the student (user) associated with this enrollment.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course associated with this enrollment.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the term associated with this enrollment (through course).
     */
    public function term()
    {
        return $this->hasOneThrough(Term::class, Course::class, 'id', 'id', 'course_id', 'term_id');
    }

    /**
     * Check if grade can be edited based on the term's grade entry date.
     *
     * @return bool
     */
    public function canEditGrade()
    {
        $term = $this->course->term;

        return now()->gte($term->grade_entry_date);
    }

    /**
     * Get available statuses.
     *
     * @return array
     */
    public static function getStatuses()
    {
        return ['enrolled', 'completed', 'dropped', 'failed'];
    }

    /**
     * Update total grade based on midterm and final grades.
     * Also update status based on final grade constraint.
     *
     * @return void
     */
    public function updateTotalGrade()
    {
        if (isset($this->midterm_grade) && isset($this->final_grade) && isset($this->course)) {
            $this->total_grade = $this->course->calculateTotalGrade($this->midterm_grade, $this->final_grade);

            // Cập nhật status dựa trên điểm tổng kết và ràng buộc điểm cuối kỳ
            // Ràng buộc: điểm cuối kỳ < 3 thì không đạt yêu cầu mặc cho điểm tổng kết > 4
            if (is_null($this->total_grade)) {
                $this->status = 'enrolled';
            } elseif (! is_null($this->final_grade) && $this->final_grade < 3) {
                $this->status = 'failed';
            } elseif ($this->total_grade < 4) {
                $this->status = 'failed';
            } else {
                $this->status = 'completed';
            }

            $this->save();
        }
    }
}
