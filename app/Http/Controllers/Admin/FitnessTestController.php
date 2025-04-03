<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\FitnessTestRequest;
use App\Services\FitnessTestService;
use Illuminate\Http\JsonResponse;

class FitnessTestController extends BaseController
{
    protected $fitnessTestService;

    public function __construct(FitnessTestService $fitnessTestService)
    {
        $this->fitnessTestService = $fitnessTestService;
    }

    /**
     * lấy danh sách tất cả bài kiểm tra thể lực
     *
     * @return JsonResponse
     */
    public function getAll(): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->getAllFitnessTests(
                request()->input('per_page', 15),
                request()->input('page', 1)
            ),
            'Lấy danh sách bài kiểm tra thể lực thành công'
        );
    }

    /**
     * lấy thông tin một bài kiểm tra thể lực
     *
     * @param int $id mã bài kiểm tra
     * @return JsonResponse
     */
    public function get(int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->getFitnessTest($id),
            'Lấy thông tin bài kiểm tra thể lực thành công'
        );
    }

    /**
     * tạo bài kiểm tra thể lực mới
     *
     * @param FitnessTestRequest $request dữ liệu bài kiểm tra
     * @return JsonResponse
     */
    public function create(FitnessTestRequest $request): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->createFitnessTest($request->validated()),
            'Tạo bài kiểm tra thể lực thành công',
            201
        );
    }

    /**
     * cập nhật thông tin bài kiểm tra thể lực
     *
     * @param FitnessTestRequest $request dữ liệu cập nhật
     * @param int $id mã bài kiểm tra
     * @return JsonResponse
     */
    public function update(FitnessTestRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->updateFitnessTest($id, $request->validated()),
            'Cập nhật bài kiểm tra thể lực thành công'
        );
    }

    /**
     * xóa bài kiểm tra thể lực
     *
     * @param int $id mã bài kiểm tra
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->deleteFitnessTest($id),
            'Xóa bài kiểm tra thể lực thành công'
        );
    }
}