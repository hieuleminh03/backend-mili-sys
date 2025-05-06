<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\MilitaryEquipmentType;
use App\Models\StudentEquipmentReceipt;
use App\Models\User;
use App\Models\YearlyEquipmentDistribution;
use App\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EquipmentController extends BaseController
{
    protected EquipmentService $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    /**
     * Lấy tất cả loại quân tư trang
     */
    public function getAllEquipmentTypes(): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->getAllEquipmentTypes(),
            'Danh sách loại quân tư trang'
        );
    }

    /**
     * Tạo mới loại quân tư trang
     */
    public function createEquipmentType(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            fn() => $this->equipmentService->createEquipmentType($request->all()),
            'Loại quân tư trang được tạo thành công',
            201
        );
    }

    /**
     * Cập nhật loại quân tư trang
     */
    public function updateEquipmentType(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            fn() => $this->equipmentService->updateEquipmentType($id, $request->all()),
            'Loại quân tư trang được cập nhật thành công'
        );
    }

    /**
     * Xóa loại quân tư trang
     */
    public function deleteEquipmentType(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->deleteEquipmentType($id),
            'Loại quân tư trang được xóa thành công'
        );
    }

    /**
     * Lấy danh sách phân phối quân tư trang theo năm
     */
    public function getDistributions(Request $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->getYearlyDistributions($request->query('year')),
            'Danh sách phân phối quân tư trang'
        );
    }

    /**
     * Tạo mới phân phối quân tư trang
     */
    public function createDistribution(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2000|max:2100',
            'equipment_type_id' => 'required|exists:military_equipment_types,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            fn() => $this->equipmentService->createDistribution($request->all()),
            'Phân phối quân tư trang được tạo thành công',
            201
        );
    }

    /**
     * Cập nhật phân phối quân tư trang
     */
    public function updateDistribution(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2000|max:2100',
            'equipment_type_id' => 'required|exists:military_equipment_types,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            fn() => $this->equipmentService->updateDistribution($id, $request->all()),
            'Phân phối quân tư trang được cập nhật thành công'
        );
    }

    /**
     * Xóa phân phối quân tư trang
     */
    public function deleteDistribution(int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->deleteDistribution($id),
            'Phân phối quân tư trang được xóa thành công'
        );
    }

    /**
     * Tạo biên nhận quân tư trang cho học viên
     */
    public function createReceipts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'distribution_id' => 'required|exists:yearly_equipment_distributions,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        return $this->executeService(
            fn() => [
                'count' => $this->equipmentService->createReceiptsForStudents(
                    $request->input('distribution_id'),
                    $request->input('student_ids')
                )
            ],
            'Biên nhận quân tư trang được tạo thành công',
            201
        );
    }

    /**
     * Lấy danh sách học viên chưa nhận đủ quân tư trang
     * 
     * @param int $year Năm cần kiểm tra
     */
    public function getStudentsWithPendingEquipment(int $year): JsonResponse
    {
        // Validate year parameter
        if ($year < 2000 || $year > 2100) {
            return $this->errorResponse('Năm không hợp lệ', ['year' => 'Năm phải từ 2000 đến 2100']);
        }

        return $this->executeService(
            fn() => $this->equipmentService->getStudentsWithPendingEquipment($year),
            'Danh sách học viên chưa nhận đủ quân tư trang'
        );
    }

    /**
     * Lấy danh sách quân tư trang của học viên
     */
    public function getStudentEquipment(int $studentId, Request $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->getStudentEquipment($studentId, $request->query('year')),
            'Danh sách quân tư trang của học viên'
        );
    }
    
    /**
     * Lấy chi tiết của một đợt phân phối quân tư trang và danh sách học viên đã nhận
     */
    public function getDistributionDetail(int $distributionId): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->getDistributionDetail($distributionId),
            'Chi tiết đợt phân phối quân tư trang'
        );
    }
    
    /**
     * Cập nhật trạng thái đã nhận/chưa nhận của biên nhận quân tư trang
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
            fn() => $this->equipmentService->updateReceiptStatus(
                $receiptId, 
                $request->input('received'), 
                $request->input('notes')
            ),
            'Trạng thái biên nhận quân tư trang được cập nhật thành công'
        );
    }
}
