<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseDetailResource;
use App\Models\Course;
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
                $courses = $user->courses()->with(['term'])->get();

                return CourseResource::collection($courses);
            },
            'Lấy danh sách học phần thành công'
        );
    }

    /**
     * Xem chi tiết của một khóa học, bao gồm thông tin lớp và danh sách học viên.
     * View detail of a course, including class information and list of students.
     * 
     * @param int $id ID of the course
     * @return JsonResponse
     */
    public function getCourseDetail($id): JsonResponse
    {
        return $this->executeService(
            function () use ($id) {
                $user = auth()->user();
                
                // Check if the student is enrolled in this course
                $isEnrolled = $user->courses()->where('courses.id', $id)->exists();
                
                if (!$isEnrolled) {
                    throw new \Illuminate\Auth\Access\AuthorizationException('Bạn không đăng ký khóa học này');
                }
                
                // Get course with term, students, and classroom information
                $course = Course::with([
                    'term',
                    'students' => function ($query) {
                        $query->select('users.id', 'users.name', 'users.email', 'users.image');
                    }
                ])->findOrFail($id);
                
                return new CourseDetailResource($course);
            },
            'Lấy thông tin chi tiết khóa học thành công'
        );
    }
}
