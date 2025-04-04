<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CourseResource;
use Illuminate\Http\JsonResponse;

class StudentCourseController extends BaseController
{
    /**
     * Lấy danh sách các học phần mà student đang đăng nhập đã đăng ký.
     * Fetches the list of courses the currently logged-in student is enrolled in.
     */
    public function getMyCourses(): JsonResponse
    {
        return $this->executeService(
            function () {
                $user = auth()->user();

                // Lấy danh sách các học phần mà user đã đăng ký
                $courses = $user->courses()->with(['term', 'manager'])->get();

                return CourseResource::collection($courses);
            },
            'Lấy danh sách học phần thành công'
        );
    }
}
