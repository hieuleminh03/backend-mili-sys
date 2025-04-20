<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\MonthlyAllowance;
use App\Models\User;
use App\Services\AllowanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AllowanceController extends BaseController
{
    protected AllowanceService $allowanceService;

    public function __construct(AllowanceService $allowanceService)
    {
        $this->allowanceService = $allowanceService;
    }

    /**
     * Lấy danh sách phụ cấp hàng tháng
     */
    public function getAllowances(Request $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->allowanceService->getAllowances($request->query('month'), $request->query('year')),
            'Danh sách phụ cấp hàng tháng'
        );
    }

    /**
     * Tạo mới phụ cấp hàng tháng
     */
    public function createAllowance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            function () use ($request) {
                // Kiểm tra xem người dùng có phải học viên không
                $user = User::find($request->input('user_id'));
                if (!$user || !$user->isStudent()) {
                    throw new \Exception('Người dùng không phải là học viên');
                }

                return $this->allowanceService->createAllowance($request->all());
            },
            'Phụ cấp hàng tháng được tạo thành công',
            201
        );
    }

    /**
     * Tạo phụ cấp hàng loạt cho nhiều học viên
     */
    public function createBulkAllowances(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            fn() => [
                'count' => $this->allowanceService->createBulkAllowances(
                    $request->input('student_ids'),
                    $request->input('month'),
                    $request->input('year'),
                    $request->input('amount')
                )
            ],
            'Phụ cấp hàng tháng được tạo thành công',
            201
        );
    }

    /**
     * Cập nhật phụ cấp hàng tháng
     */
    public function updateAllowance(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|required|exists:users,id',
            'month' => 'sometimes|required|integer|min:1|max:12',
            'year' => 'sometimes|required|integer|min:2000|max:2100',
            'amount' => 'sometimes|required|numeric|min:0',
            'received' => 'sometimes|required|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            function () use ($request, $id) {
                // Kiểm tra nếu có user_id, người dùng có phải học viên không
                if ($request->has('user_id')) {
                    $user = User::find($request->input('user_id'));
                    if (!$user || !$user->isStudent()) {
                        throw new \Exception('Người dùng không phải là học viên');
                    }
                }

                return $this->allowanceService->updateAllowance($id, $request->all());
            },
            'Phụ cấp hàng tháng được cập nhật thành công'
        );
    }

    /**
     * Xóa phụ cấp hàng tháng
     */
    public function deleteAllowance(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->allowanceService->deleteAllowance($id),
            'Phụ cấp hàng tháng được xóa thành công'
        );
    }

    /**
     * Lấy danh sách học viên chưa nhận phụ cấp
     */
public function getStudentsWithPendingAllowances(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'month' => 'sometimes|integer|min:1|max:12',
            'year' => 'sometimes|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        $month = $request->input('month');
        $year = $request->input('year');

        return $this->executeService(
            fn() => $this->allowanceService->getStudentsWithPendingAllowances($month, $year),
            'Danh sách học viên chưa nhận phụ cấp'
        );
    }

    /**
     * Lấy danh sách phụ cấp của học viên
     */
    public function getStudentAllowances(int $studentId, Request $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->allowanceService->getStudentAllowances(
                $studentId,
                $request->query('month'),
                $request->query('year')
            ),
            'Danh sách phụ cấp của học viên'
        );
    }
}
