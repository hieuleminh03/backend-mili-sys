<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\TermRequest;
use App\Services\TermService;
use Illuminate\Http\JsonResponse;

class TermController extends BaseController
{
    protected $termService;

    public function __construct(TermService $termService)
    {
        $this->termService = $termService;
    }

    /**
     * danh sách các học kỳ
     *
     * @return JsonResponse danh sách các học kỳ
     */
    public function getAll(): JsonResponse
    {
        return $this->executeService(
            fn() => $this->termService->getAllTerms(), 
            'Lấy danh sách học kỳ thành công', 
            200
        );
    }

    /**
     * tạo mới một học kỳ
     *
     * @param TermRequest $request dữ liệu học kỳ
     * @return JsonResponse kết quả 
     */
    public function create(TermRequest $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->termService->createTerm($request->validated()),
            'Thêm mới học kỳ thành công',
            201
        );
    }

    /**
     * lấy thông tin một học kỳ
     *
     * @param int $id mã học kỳ
     * @return JsonResponse kết quả 
     */
    public function get(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->termService->getTerm($id)
        );
    }

    /**
     * cập nhật thông tin một học kỳ
     *
     * @param TermRequest $request dữ liệu học kỳ
     * @param int $id mã học kỳ
     * @return JsonResponse kết quả 
     */
    public function update(TermRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->termService->updateTerm($id, $request->validated()),
            'Cập nhật học kỳ thành công'
        );
    }

    /**
     * xóa một học kỳ
     *
     * @param int $id mã học kỳ
     * @return JsonResponse kết quả 
     */
    public function delete(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->termService->deleteTerm($id),
            'Xóa học kỳ thành công'
        );
    }
} 