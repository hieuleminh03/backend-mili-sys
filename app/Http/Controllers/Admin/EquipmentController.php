<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\DistributionCreateRequest;
use App\Http\Requests\DistributionUpdateRequest;
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
    public function createDistribution(DistributionCreateRequest $request): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->createDistribution($request->validated()),
            'Phân phối quân tư trang được tạo thành công',
            201
        );
    }

    /**
     * Cập nhật phân phối quân tư trang
     */
    public function updateDistribution(DistributionUpdateRequest $request, int $id): JsonResponse
    {
        return $this->executeService(
            fn() => $this->equipmentService->updateDistribution($id, $request->validated()),
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
        // Basic validation first
        $validator = Validator::make($request->all(), [
            'distribution_id' => 'required|exists:yearly_equipment_distributions,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Lỗi dữ liệu nhập vào', $validator->errors());
        }

        // Business logic validation
        $distributionId = $request->input('distribution_id');
        $studentIds = $request->input('student_ids');
        
        try {
            // Validate số lượng thiết bị vs số học viên nhận
            $distribution = YearlyEquipmentDistribution::findOrFail($distributionId);
            $existingReceiptsCount = StudentEquipmentReceipt::where('distribution_id', $distributionId)->count();
            $newStudentsCount = count(array_unique($studentIds));
            $totalStudentsAfterAdd = $existingReceiptsCount + $newStudentsCount;
            
            if ($totalStudentsAfterAdd > $distribution->quantity) {
                $maxCanAdd = $distribution->quantity - $existingReceiptsCount;
                return $this->errorResponse(
                    'Số lượng học viên nhận vượt quá số lượng thiết bị có sẵn',
                    [
                        'equipment_quantity' => $distribution->quantity,
                        'existing_receipts' => $existingReceiptsCount,
                        'new_students' => $newStudentsCount,
                        'total_after_add' => $totalStudentsAfterAdd,
                        'message' => "Hiện có {$existingReceiptsCount} học viên đã được phân phối, bạn chỉ có thể thêm tối đa {$maxCanAdd} học viên nữa (số lượng tối đa cho phép: {$distribution->quantity})"
                    ]
                );
            }
            
            // Kiểm tra duplicate student IDs trong request
            if (count($studentIds) !== count(array_unique($studentIds))) {
                return $this->errorResponse(
                    'Danh sách học viên có ID trùng lặp',
                    ['student_ids' => 'Danh sách học viên có ID trùng lặp']
                );
            }
            
            // Kiểm tra học viên đã có biên nhận cho đợt phân phối này chưa
            $existingStudentIds = StudentEquipmentReceipt::where('distribution_id', $distributionId)
                ->whereIn('user_id', $studentIds)
                ->pluck('user_id')
                ->toArray();
                
            if (!empty($existingStudentIds)) {
                return $this->errorResponse(
                    'Một số học viên đã có biên nhận cho đợt phân phối này',
                    [
                        'student_ids' => 'Một số học viên đã có biên nhận cho đợt phân phối này: ' . implode(', ', $existingStudentIds),
                        'duplicate_students' => $existingStudentIds
                    ]
                );
            }

            return $this->executeService(
                fn() => [
                    'count' => $this->equipmentService->createReceiptsForStudents(
                        $distributionId,
                        $studentIds
                    )
                ],
                'Biên nhận quân tư trang được tạo thành công',
                201
            );
        } catch (\Exception $e) {
            \Log::error('Equipment receipt creation error: ' . $e->getMessage());
            return $this->errorResponse(
                'Có lỗi xảy ra trong quá trình tạo biên nhận',
                ['error' => $e->getMessage()]
            );
        }
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
