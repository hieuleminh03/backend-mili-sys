<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

class StudentGradeController extends BaseController
{
    /**
     * Lấy danh sách điểm số của student đang đăng nhập cho các học phần đã đăng ký.
     * Fetches the grades of the currently logged-in student for their enrolled courses.
     */
    public function getMyGrades(): JsonResponse
    {
        return $this->executeService(
            function () {
                $user = auth()->user();

                // Lấy danh sách các học phần cùng với thông tin điểm từ bảng pivot và thông tin kỳ học
                $enrollments = $user->courses()->with('term')->get(); // courses() already includes pivot data

                // Transform data to include necessary grade information and term details
                return $enrollments->map(function ($course) {
                    return [
                        'course_id' => $course->id,
                        'course_name' => $course->subject_name,
                        'term_id' => $course->term_id,
                        'term_name' => $course->term->name,
                        'midterm_grade' => $course->pivot->midterm_grade,
                        'final_grade' => $course->pivot->final_grade,
                        'total_grade' => $course->pivot->total_grade,
                        'status' => $course->pivot->status,
                        'notes' => $course->pivot->notes,
                    ];
                });
            },
            'Lấy danh sách điểm số thành công'
        );
    }
}
