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
     *
     * @param ClassService $classService
     */
    public function __construct(ClassService $classService)
    {
        $this->classService = $classService;
    }

    /**
     * Lấy danh sách tất cả các lớp
     *
     * @return JsonResponse
     */
    public function getAllClasses(): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->getAllClasses(),
            'Lấy danh sách lớp thành công'
        );
    }

    /**
     * Lấy thông tin chi tiết của một lớp
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getClass(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->getClass($id),
            'Lấy thông tin chi tiết lớp thành công'
        );
    }

    /**
     * Tạo lớp mới
     *
     * @param ClassCreateRequest $request
     * @return JsonResponse
     */
    public function createClass(ClassCreateRequest $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->createClass($request->validated()),
            'Tạo lớp thành công',
            201
        );
    }

    /**
     * Cập nhật thông tin lớp
     *
     * @param ClassUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateClass(ClassUpdateRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->updateClass($id, $request->validated()),
            'Cập nhật lớp thành công'
        );
    }

    /**
     * Xóa lớp
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteClass(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->deleteClass($id),
            'Xóa lớp thành công'
        );
    }

    /**
     * Thêm học viên vào lớp
     *
     * @param StudentClassAddRequest $request
     * @param int $classId
     * @return JsonResponse
     */
    public function addStudentToClass(StudentClassAddRequest $request, int $classId): JsonResponse
    {
        $data = $request->validated();
        $userId = $data['user_id'];
        unset($data['user_id']);
        
        return $this->executeService(
            fn() => $this->classService->addStudentToClass($classId, $userId, $data),
            'Thêm học viên vào lớp thành công',
            201
        );
    }

    /**
     * Cập nhật thông tin học viên trong lớp
     *
     * @param StudentClassUpdateRequest $request
     * @param int $classId
     * @param int $studentId
     * @return JsonResponse
     */
    public function updateStudentInClass(StudentClassUpdateRequest $request, int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->updateStudentClass($classId, $studentId, $request->validated()),
            'Cập nhật thông tin học viên thành công'
        );
    }

    /**
     * Xóa học viên khỏi lớp
     *
     * @param int $classId
     * @param int $studentId
     * @return JsonResponse
     */
    public function removeStudentFromClass(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->removeStudentFromClass($classId, $studentId),
            'Xóa học viên khỏi lớp thành công'
        );
    }

    /**
     * Lấy thông tin chi tiết của học viên trong lớp
     *
     * @param int $classId
     * @param int $studentId
     * @return JsonResponse
     */
    public function getStudentClassDetail(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->getStudentClassDetail($classId, $studentId),
            'Lấy thông tin chi tiết học viên thành công'
        );
    }

    /**
     * Chỉ định học viên làm lớp trưởng
     *
     * @param int $classId
     * @param int $studentId
     * @return JsonResponse
     */
    public function assignMonitor(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->assignMonitor($classId, $studentId),
            'Chỉ định lớp trưởng thành công'
        );
    }

    /**
     * Chỉ định học viên làm lớp phó
     *
     * @param int $classId
     * @param int $studentId
     * @return JsonResponse
     */
    public function assignViceMonitor(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->assignViceMonitor($classId, $studentId),
            'Chỉ định lớp phó thành công'
        );
    }

    /**
     * Chỉ định học viên làm thành viên thường
     *
     * @param int $classId
     * @param int $studentId
     * @return JsonResponse
     */
    public function assignStudent(int $classId, int $studentId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->classService->assignStudent($classId, $studentId),
            'Chỉ định thành viên thường thành công'
        );
    }
} 