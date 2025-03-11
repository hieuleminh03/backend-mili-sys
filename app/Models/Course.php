<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'subject_name',
        'term_id',
        'manager_id',
    ];

    /**
     * Get the term that this course belongs to.
     */
    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the manager of this course.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the students enrolled in this course.
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'student_courses')
            ->withPivot(['grade', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the student course relationships.
     */
    public function studentCourses()
    {
        return $this->hasMany(StudentCourse::class);
    }

    /**
     * Get all students with their grades in this course.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStudentsWithGrades()
    {
        return $this->students()
            ->with('studentCourses')
            ->get()
            ->map(function ($student) {
                $enrollment = $student->pivot;
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'grade' => $enrollment->grade,
                    'status' => $enrollment->status,
                    'notes' => $enrollment->notes,
                ];
            });
    }
}
