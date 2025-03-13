<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\CourseRequest;
use App\Http\Requests\EnrollmentRequest;
use App\Http\Requests\GradeRequest;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;

class CourseController extends BaseController
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * danh sách các lớp học
     *
     * @return JsonResponse danh sách các lớp học
     */
    public function getAll(): JsonResponse
    {
        return $this->executeService(
            fn() => $this->courseService->getAllCourses(),
            'Lấy danh sách lớp học thành công'
        );
    }

    /**
     * tạo mới một lớp học
     *
     * @param CourseRequest $request dữ liệu lớp học
     * @return JsonResponse kết quả 
     */
    public function create(CourseRequest $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->courseService->createCourse($request->validated()),
            'Thêm mới lớp học thành công',
            201
        );
    }

    /**
     * lấy thông tin một lớp học
     *
     * @param int $id mã lớp học
     * @return JsonResponse kết quả 
     */
    public function get(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->courseService->getCourse($id),
            'Lấy thông tin lớp học thành công'
        );
    }

    /**
     * cập nhật thông tin lớp học
     *
     * @param CourseRequest $request dữ liệu lớp học
     * @param int $id mã lớp học
     * @return JsonResponse kết quả 
     */
    public function update(CourseRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->courseService->updateCourse($id, $request->validated()),
            'Cập nhật lớp học thành công'
        );
    }

    /**
     * xóa một lớp học
     *
     * @param int $id mã lớp học
     * @return JsonResponse kết quả 
     */
    public function delete(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->courseService->deleteCourse($id),
            'Xóa lớp học thành công'
        );
    }

    /**
     * lấy danh sách sinh viên tham gia một lớp học
     *
     * @param int $id mã lớp học
     * @return JsonResponse kết quả 
     */
    public function getStudents(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->courseService->getCourseStudents($id),
            'Lấy danh sách sinh viên thành công'
        );
    }

    /**
     * đăng ký sinh viên vào lớp học
     *
     * @param EnrollmentRequest $request dữ liệu đăng ký
     * @param int $id mã lớp học
     * @return JsonResponse kết quả 
     */
    public function enrollStudent(EnrollmentRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->courseService->enrollStudent($id, $request->user_id),
            'Đăng ký thành công',
            201
        );
    }

    /**
     * cập nhật điểm sinh viên trong một lớp học
     *
     * @param GradeRequest $request dữ liệu điểm
     * @param int $courseId mã lớp học
     * @param int $userId mã sinh viên
     * @return JsonResponse kết quả 
     */
    public function updateStudentGrade(GradeRequest $request, int $courseId, int $userId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->courseService->updateStudentGrade($courseId, $userId, $request->validated()),
            'Cập nhật điểm thành công'
        );
    }
} 