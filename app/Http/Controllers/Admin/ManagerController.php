<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ManagerDetailUpdateRequest;
use App\Services\ManagerService;
use Illuminate\Http\JsonResponse;

class ManagerController extends BaseController
{
    protected $managerService;

    /**
     * khởi tạo controller với service
     *
     * @param ManagerService $managerService
     */
    public function __construct(ManagerService $managerService)
    {
        $this->managerService = $managerService;
    }

    /**
     * lấy danh sách các manager
     *
     * @return JsonResponse
     */
    public function getAllManagers(): JsonResponse
    {
        return $this->executeService(
            fn() => $this->managerService->getAllManagers(),
            'Lấy danh sách quản lý thành công'
        );
    }

    /**
     * xem chi tiết thông tin của một manager
     *
     * @param int $id ID của manager
     * @return JsonResponse
     */
    public function getManagerDetail(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->managerService->getManagerDetail($id),
            'Lấy thông tin chi tiết quản lý thành công'
        );
    }

    /**
     * cập nhật thông tin chi tiết của manager
     *
     * @param ManagerDetailUpdateRequest $request
     * @param int $id ID của manager
     * @return JsonResponse
     */
    public function updateManagerDetail(ManagerDetailUpdateRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->managerService->updateManagerDetail($id, $request->validated()),
            'Cập nhật thông tin quản lý thành công'
        );
    }
} 