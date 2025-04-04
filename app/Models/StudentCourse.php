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
        'final_grade' => 'decimal:2',
        'total_grade' => 'decimal:2',
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
     *
     * @return void
     */
    public function updateTotalGrade()
    {
        if (isset($this->midterm_grade) && isset($this->final_grade) && isset($this->course)) {
            $this->total_grade = $this->course->calculateTotalGrade($this->midterm_grade, $this->final_grade);
            $this->save();
        }
    }
}
