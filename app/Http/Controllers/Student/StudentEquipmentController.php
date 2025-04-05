<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Models\StudentEquipmentReceipt;
use App\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentEquipmentController extends BaseController
{
    protected EquipmentService $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    /**
     * Lấy danh sách quân tư trang của học viên đăng nhập
     */
    public function getMyEquipment(Request $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->getStudentEquipment(auth()->id(), $request->query('year')),
            'Danh sách quân tư trang của tôi'
        );
    }

    /**
     * Cập nhật trạng thái nhận quân tư trang
     */
    public function updateReceiptStatus(Request $request, int $receiptId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'received' => 'required|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            function () use ($request, $receiptId) {
                // Kiểm tra xem biên nhận có thuộc về học viên này không
                $receipt = StudentEquipmentReceipt::findOrFail($receiptId);
                if ($receipt->user_id !== auth()->id()) {
                    throw new \Exception('Không có quyền cập nhật biên nhận này', 403);
                }

                return $this->equipmentService->updateReceiptStatus(
                    $receiptId,
                    $request->input('received'),
                    $request->input('notes')
                );
            },
            'Trạng thái nhận quân tư trang đã được cập nhật'
        );
    }
}