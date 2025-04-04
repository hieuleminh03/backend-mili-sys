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

                // Lấy danh sách các học phần cùng với thông tin điểm từ bảng pivot
                $enrollments = $user->courses()->get(); // courses() already includes pivot data

                // Transform data to include only necessary grade information
                return $enrollments->map(function ($course) {
                    return [
                        'course_id' => $course->id,
                        'course_name' => $course->name,
                        'grade' => $course->pivot->grade, // Access grade from pivot
                        'status' => $course->pivot->status, // Optionally include status
                        'notes' => $course->pivot->notes, // Optionally include notes
                    ];
                });
            },
            'Lấy danh sách điểm số thành công'
        );
    }
}
