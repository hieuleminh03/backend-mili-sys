<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\BulkEnrollmentRequest;
use App\Http\Requests\BulkGradeUpdateRequest;
use App\Http\Requests\CourseRequest;
use App\Http\Requests\EnrollmentRequest;
use App\Http\Requests\GradeRequest;
use App\Http\Requests\TermIdRequest;
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
            fn () => $this->courseService->getAllCourses(),
            'Lấy danh sách lớp học thành công'
        );
    }

    /**
     * tạo mới một lớp học
     *
     * @param  CourseRequest  $request  dữ liệu lớp học
     * @return JsonResponse kết quả
     */
    public function create(CourseRequest $request): JsonResponse
    {
        return $this->executeService(
            fn () => $this->courseService->createCourse($request->validated()),
            'Thêm mới lớp học thành công',
            201
        );
    }

    /**
     * lấy thông tin một lớp học
     *
     * @param  int  $id  mã lớp học
     * @return JsonResponse kết quả
     */
    public function get(int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->courseService->getCourse($id),
            'Lấy thông tin lớp học thành công'
        );
    }

    /**
     * cập nhật thông tin lớp học
     *
     * @param  CourseRequest  $request  dữ liệu lớp học
     * @param  int  $id  mã lớp học
     * @return JsonResponse kết quả
     */
    public function update(CourseRequest $request, $id = null): JsonResponse
    {
        if ($id === null) {
            return $this->errorResponse('Mã lớp học không được cung cấp', ['id' => ['Vui lòng cung cấp mã lớp học']], 400);
        }

        return $this->executeService(
            fn () => $this->courseService->updateCourse((int) $id, $request->validated()),
            'Cập nhật lớp học thành công'
        );
    }

    /**
     * xóa một lớp học
     *
     * @param  int  $id  mã lớp học
     * @return JsonResponse kết quả
     */
    public function delete(int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->courseService->deleteCourse($id),
            'Xóa lớp học thành công'
        );
    }

    /**
     * lấy danh sách sinh viên tham gia một lớp học
     *
     * @param  int  $id  mã lớp học
     * @return JsonResponse kết quả
     */
    public function getStudents(int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->courseService->getCourseStudents($id),
            'Lấy danh sách sinh viên thành công'
        );
    }

    /**
     * đăng ký sinh viên vào lớp học
     *
     * @param  EnrollmentRequest  $request  dữ liệu đăng ký
     * @param  int  $id  mã lớp học
     * @return JsonResponse kết quả
     */
    public function enrollStudent(EnrollmentRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->courseService->enrollStudent($id, $request->user_id),
            'Đăng ký sinh viên thành công',
            201
        );
    }

    /**
     * hủy đăng ký sinh viên khỏi lớp học
     *
     * @param  string  $courseId  mã lớp học
     * @param  string  $userId  mã sinh viên
     * @return JsonResponse kết quả
     */
    public function unenrollStudent(string $courseId, string $userId): JsonResponse
    {
        if (! is_numeric($courseId)) {
            return $this->errorResponse('Mã lớp học không hợp lệ', ['courseId' => ['Mã lớp học phải là số nguyên']], 400);
        }

        if (! is_numeric($userId)) {
            return $this->errorResponse('Mã sinh viên không hợp lệ', ['userId' => ['Mã sinh viên phải là số nguyên']], 400);
        }

        return $this->executeService(
            fn () => $this->courseService->unenrollStudent((int) $courseId, (int) $userId),
            'Hủy đăng ký sinh viên thành công'
        );
    }

    /**
     * cập nhật điểm sinh viên trong một lớp học
     *
     * @param  GradeRequest  $request  dữ liệu điểm
     * @param  string  $courseId  mã lớp học
     * @param  string  $userId  mã sinh viên
     * @return JsonResponse kết quả
     */
    public function updateStudentGrade(GradeRequest $request, string $courseId, string $userId): JsonResponse
    {
        if (! is_numeric($courseId)) {
            return $this->errorResponse('Mã lớp học không hợp lệ', ['courseId' => ['Mã lớp học phải là số nguyên']], 400);
        }

        if (! is_numeric($userId)) {
            return $this->errorResponse('Mã sinh viên không hợp lệ', ['userId' => ['Mã sinh viên phải là số nguyên']], 400);
        }

        return $this->executeService(
            fn () => $this->courseService->updateStudentGrade((int) $courseId, (int) $userId, $request->validated()),
            'Cập nhật điểm thành công'
        );
    }

    /**
     * lấy danh sách lớp học theo kỳ học
     *
     * @param  int  $id  mã kỳ học
     * @return JsonResponse danh sách lớp học
     */
    public function getAllByTerm(int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->courseService->getCoursesByTerm($id),
            'Lấy danh sách lớp học theo kỳ học thành công'
        );
    }

    /**
     * đăng ký hàng loạt sinh viên vào lớp học
     *
     * @param  int  $id  course id
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkEnrollStudents(BulkEnrollmentRequest $request, int $id)
    {
        try {
            $result = $this->courseService->bulkEnrollStudents($id, $request->student_ids);

            $status = 'success';
            if (count($result['success']) === 0 && (count($result['failed']) > 0 || count($result['already_enrolled']) > 0)) {
                $status = 'error';
            } elseif (count($result['success']) > 0 && (count($result['failed']) > 0 || count($result['already_enrolled']) > 0)) {
                $status = 'partial';
            }

            // Lấy message từ result và loại bỏ khỏi result để tránh trùng lặp
            $message = $result['message'];
            unset($result['message']);

            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    /**
     * cập nhật điểm hàng loạt cho sinh viên trong một lớp học
     *
     * @param  int  $id  mã lớp học
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateGrades(BulkGradeUpdateRequest $request, int $id)
    {
        try {
            $result = $this->courseService->bulkUpdateGrades($id, $request->grades);

            $status = 'success';
            if (count($result['success']) === 0 && count($result['failed']) > 0) {
                $status = 'error';
            } elseif (count($result['success']) > 0 && count($result['failed']) > 0) {
                $status = 'partial';
            }

            // Lấy message từ result và loại bỏ khỏi result để tránh trùng lặp
            $message = $result['message'];
            unset($result['message']);

            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }
}
