<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentCourseController extends Controller
{
    /**
     * Lấy danh sách các học phần mà student đang đăng nhập đã đăng ký.
     * Fetches the list of courses the currently logged-in student is enrolled in.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse Trả về danh sách các học phần dưới dạng JSON, sử dụng CourseResource. Returns a list of courses as JSON using CourseResource.
     */
    public function getMyCourses(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Lấy danh sách các học phần mà user đã đăng ký
        // Eager load related data if necessary, e.g., term, manager
        $courses = $user->courses()->with(['term', 'manager'])->get();

        // Trả về danh sách học phần sử dụng CourseResource collection
        // Return the list of courses using CourseResource collection
        return response()->json([
            'status' => 'success',
            'data' => CourseResource::collection($courses)
        ]);
    }
}
