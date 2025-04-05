<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Models\MonthlyAllowance;
use App\Services\AllowanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentAllowanceController extends BaseController
{
    protected AllowanceService $allowanceService;

    public function __construct(AllowanceService $allowanceService)
    {
        $this->allowanceService = $allowanceService;
    }

    /**
     * Lấy danh sách phụ cấp của học viên đăng nhập
     */
    public function getMyAllowances(Request $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->allowanceService->getStudentAllowances(
                auth()->id(),
                $request->query('month'),
                $request->query('year')
            ),
            'Danh sách phụ cấp của tôi'
        );
    }

    /**
     * Cập nhật trạng thái nhận phụ cấp
     */
    public function updateAllowanceStatus(Request $request, int $allowanceId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'received' => 'required|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            function () use ($request, $allowanceId) {
                // Kiểm tra xem phụ cấp có thuộc về học viên này không
                $allowance = MonthlyAllowance::findOrFail($allowanceId);
                if ($allowance->user_id !== auth()->id()) {
                    throw new \Exception('Không có quyền cập nhật phụ cấp này', 403);
                }

                return $this->allowanceService->updateAllowanceStatus(
                    $allowanceId,
                    $request->input('received'),
                    $request->input('notes')
                );
            },
            'Trạng thái nhận phụ cấp đã được cập nhật'
        );
    }
}