<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ClassCreateRequest;
use App\Http\Requests\ClassUpdateRequest;
use App\Http\Requests\StudentClassAddRequest;
use App\Http\Requests\StudentClassUpdateRequest;
use App\Services\ClassService;
use Illuminate\Http\JsonResponse;

class ClassController extends BaseController
{
    protected $classService;

    /**
     * Constructor
     */
    public function __construct(ClassService $classService)
    {
        $this->classService = $classService;
    }

    /**
     * Lấy danh sách tất cả các lớp
     */
    public function getAllClasses(): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->getAllClasses(),
            'Lấy danh sách lớp thành công'
        );
    }

    /**
     * Lấy thông tin chi tiết của một lớp
     */
    public function getClass(int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->getClass($id),
            'Lấy thông tin chi tiết lớp thành công'
        );
    }

    /**
     * Tạo lớp mới
     */
    public function createClass(ClassCreateRequest $request): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->createClass($request->validated()),
            'Tạo lớp thành công',
            201
        );
    }

    /**
     * Cập nhật thông tin lớp
     */
    public function updateClass(ClassUpdateRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->updateClass($id, $request->validated()),
            'Cập nhật lớp thành công'
        );
    }

    /**
     * Xóa lớp
     */
    public function deleteClass(int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->deleteClass($id),
            'Xóa lớp thành công'
        );
    }

    /**
     * Thêm học viên vào lớp
     */
    public function addStudentToClass(StudentClassAddRequest $request, int $classId): JsonResponse
    {
        $data = $request->validated();
        $userId = $data['user_id'];
        unset($data['user_id']);

        return $this->executeService(
            fn () => $this->classService->addStudentToClass($classId, $userId, $data),
            'Thêm học viên vào lớp thành công',
            201
        );
    }

    /**
     * Cập nhật thông tin học viên trong lớp
     */
    public function updateStudentInClass(StudentClassUpdateRequest $request, int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->updateStudentClass($classId, $studentId, $request->validated()),
            'Cập nhật thông tin học viên thành công'
        );
    }

    /**
     * Xóa học viên khỏi lớp
     */
    public function removeStudentFromClass(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->removeStudentFromClass($classId, $studentId),
            'Xóa học viên khỏi lớp thành công'
        );
    }

    /**
     * Lấy thông tin chi tiết của học viên trong lớp
     */
    public function getStudentClassDetail(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->getStudentClassDetail($classId, $studentId),
            'Lấy thông tin chi tiết học viên thành công'
        );
    }

    /**
     * Chỉ định học viên làm lớp trưởng
     */
    public function assignMonitor(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->assignMonitor($classId, $studentId),
            'Chỉ định lớp trưởng thành công'
        );
    }

    /**
     * Chỉ định học viên làm lớp phó
     */
    public function assignViceMonitor(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->assignViceMonitor($classId, $studentId),
            'Chỉ định lớp phó thành công'
        );
    }

    /**
     * Chỉ định học viên làm thành viên thường
     */
    public function assignStudent(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn () => $this->classService->assignStudent($classId, $studentId),
            'Chỉ định thành viên thường thành công'
        );
    }
}
