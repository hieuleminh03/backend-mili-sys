<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentGradeController extends Controller
{
    /**
     * Lấy danh sách điểm số của student đang đăng nhập cho các học phần đã đăng ký.
     * Fetches the grades of the currently logged-in student for their enrolled courses.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse Trả về danh sách điểm số dưới dạng JSON. Returns a list of grades as JSON.
     */
    public function getMyGrades(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Lấy danh sách các học phần cùng với thông tin điểm từ bảng pivot
        // Fetch the list of courses along with grade information from the pivot table
        $enrollments = $user->courses()->get(); // courses() already includes pivot data

        // Transform data to include only necessary grade information
        $gradesData = $enrollments->map(function ($course) {
            return [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'grade' => $course->pivot->grade, // Access grade from pivot
                'status' => $course->pivot->status, // Optionally include status
                'notes' => $course->pivot->notes, // Optionally include notes
            ];
        });

        // Trả về danh sách điểm số
        // Return the list of grades
        return response()->json([
            'status' => 'success',
            'data' => $gradesData
        ]);
    }
}
