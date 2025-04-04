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
        'enroll_limit',
        'midterm_weight',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'midterm_weight' => 'decimal:2',
        'enroll_limit' => 'integer',
    ];

    /**
     * Get the term that this course belongs to.
     */
    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the students enrolled in this course.
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'student_courses')
            ->withPivot(['midterm_grade', 'final_grade', 'total_grade', 'status', 'notes'])
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
                    'midterm_grade' => $enrollment->midterm_grade,
                    'final_grade' => $enrollment->final_grade,
                    'total_grade' => $enrollment->total_grade,
                    'status' => $enrollment->status,
                    'notes' => $enrollment->notes,
                ];
            });
    }

    /**
     * tự động generate mã lớp học 6 chữ số
     */
    public static function generateCode(): string
    {
        do {
            $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * tính điểm tổng kết dựa vào hệ số và điểm giữa kỳ, cuối kỳ
     *
     * @param  float  $midtermGrade  điểm giữa kỳ
     * @param  float  $finalGrade  điểm cuối kỳ
     * @return float điểm tổng kết
     */
    public function calculateTotalGrade(float $midtermGrade, float $finalGrade): float
    {
        return round($this->midterm_weight * $midtermGrade + (1 - $this->midterm_weight) * $finalGrade, 2);
    }

    /**
     * kiểm tra xem lớp học còn chỗ không
     *
     * @return bool true nếu còn chỗ để đăng ký
     */
    public function hasAvailableSlots(): bool
    {
        $currentStudentCount = $this->studentCourses()->count();

        return $currentStudentCount < $this->enroll_limit;
    }

    /**
     * lấy số lượng sinh viên hiện tại trong lớp
     *
     * @return int số lượng sinh viên hiện tại
     */
    public function getCurrentStudentCount(): int
    {
        return $this->studentCourses()->count();
    }
}
