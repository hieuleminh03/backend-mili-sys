<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StudentClassUpdateRequest;
use App\Services\ClassService;
use Illuminate\Http\JsonResponse;

class ManagerClassController extends BaseController
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
     * Lấy thông tin lớp của manager đang đăng nhập
     *
     * @return JsonResponse
     */
    public function getMyClass(): JsonResponse
    {
        $managerId = auth()->id();
        
        return $this->executeService(
            fn() => $this->classService->getManagerClass($managerId),
            'Lấy thông tin lớp quản lý thành công'
        );
    }

    /**
     * Lấy thông tin chi tiết của học viên trong lớp
     *
     * @param int $studentId
     * @return JsonResponse
     */
    public function getStudentDetail(int $studentId): JsonResponse
    {
        $managerId = auth()->id();
        
        return $this->executeService(
            function() use ($managerId, $studentId) {
                $class = $this->classService->getManagerClass($managerId);
                return $this->classService->getStudentClassDetail($class['id'], $studentId);
            },
            'Lấy thông tin chi tiết học viên thành công'
        );
    }

    /**
     * Cập nhật thông tin học viên trong lớp
     *
     * @param StudentClassUpdateRequest $request
     * @param int $studentId
     * @return JsonResponse
     */
    public function updateStudent(StudentClassUpdateRequest $request, int $studentId): JsonResponse
    {
        $managerId = auth()->id();
        
        return $this->executeService(
            function() use ($managerId, $studentId, $request) {
                $class = $this->classService->getManagerClass($managerId);
                return $this->classService->updateStudentClass($class['id'], $studentId, $request->validated());
            },
            'Cập nhật thông tin học viên thành công'
        );
    }

    /**
     * Chỉ định lớp trưởng
     *
     * @param int $studentId
     * @return JsonResponse
     */
    public function assignMonitor(int $studentId): JsonResponse
    {
        $managerId = auth()->id();
        
        return $this->executeService(
            function() use ($managerId, $studentId) {
                $class = $this->classService->getManagerClass($managerId);
                return $this->classService->assignMonitor($class['id'], $studentId);
            },
            'Chỉ định lớp trưởng thành công'
        );
    }

    /**
     * Chỉ định lớp phó
     *
     * @param int $studentId
     * @return JsonResponse
     */
    public function assignViceMonitor(int $studentId): JsonResponse
    {
        $managerId = auth()->id();
        
        return $this->executeService(
            function() use ($managerId, $studentId) {
                $class = $this->classService->getManagerClass($managerId);
                return $this->classService->assignViceMonitor($class['id'], $studentId);
            },
            'Chỉ định lớp phó thành công'
        );
    }

    /**
     * Chỉ định học viên làm thành viên thường
     *
     * @param int $studentId
     * @return JsonResponse
     */
    public function assignStudent(int $studentId): JsonResponse
    {
        $managerId = auth()->id();
        
        return $this->executeService(
            function() use ($managerId, $studentId) {
                $class = $this->classService->getManagerClass($managerId);
                return $this->classService->assignStudent($class['id'], $studentId);
            },
            'Chỉ định thành viên thường thành công'
        );
    }
}