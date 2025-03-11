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
        'grade',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'grade' => 'decimal:2',
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
}
