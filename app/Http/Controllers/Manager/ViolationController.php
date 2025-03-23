<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ViolationCreateRequest;
use App\Http\Requests\ViolationUpdateRequest;
use App\Services\ViolationService;
use Illuminate\Http\JsonResponse;

class ViolationController extends BaseController
{
    protected $violationService;

    /**
     * khởi tạo controller với service
     *
     * @param ViolationService $violationService
     */
    public function __construct(ViolationService $violationService)
    {
        $this->violationService = $violationService;
    }

    /**
     * lấy danh sách vi phạm của một học viên
     *
     * @param int $studentId ID của học viên
     * @return JsonResponse
     */
    public function getStudentViolations(int $studentId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->violationService->getStudentViolations($studentId),
            'Lấy danh sách vi phạm thành công'
        );
    }

    /**
     * tạo mới bản ghi vi phạm
     *
     * @param ViolationCreateRequest $request
     * @return JsonResponse
     */
    public function create(ViolationCreateRequest $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->violationService->createViolation(
                $request->validated(),
                auth()->id()
            ),
            'Tạo bản ghi vi phạm thành công',
            201
        );
    }

    /**
     * cập nhật thông tin vi phạm
     *
     * @param ViolationUpdateRequest $request
     * @param int $id ID của vi phạm
     * @return JsonResponse
     */
    public function update(ViolationUpdateRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->violationService->updateViolation(
                $id,
                $request->validated(),
                auth()->id()
            ),
            'Cập nhật vi phạm thành công'
        );
    }

    /**
     * xóa bản ghi vi phạm
     *
     * @param int $id ID của vi phạm
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->violationService->deleteViolation($id, auth()->id()),
            'Xóa vi phạm thành công'
        );
    }
} 