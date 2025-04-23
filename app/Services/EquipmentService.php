<?php

namespace App\Services;

use App\Models\MilitaryEquipmentType;
use App\Models\StudentEquipmentReceipt;
use App\Models\User;
use App\Models\YearlyEquipmentDistribution;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EquipmentService
{
    /**
     * Lấy tất cả loại quân tư trang
     * 
     * @return Collection<MilitaryEquipmentType>
     */
    public function getAllEquipmentTypes(): Collection
    {
        return MilitaryEquipmentType::all();
    }

    /**
     * Tạo mới một loại quân tư trang
     * 
     * @param array $data Dữ liệu cho loại quân tư trang
     * @return MilitaryEquipmentType
     */
    public function createEquipmentType(array $data): MilitaryEquipmentType
    {
        return MilitaryEquipmentType::create($data);
    }

    /**
     * Cập nhật loại quân tư trang
     * 
     * @param int $id ID của loại quân tư trang
     * @param array $data Dữ liệu cập nhật
     * @return MilitaryEquipmentType
     */
    public function updateEquipmentType(int $id, array $data): MilitaryEquipmentType
    {
        $equipmentType = MilitaryEquipmentType::findOrFail($id);
        $equipmentType->update($data);
        return $equipmentType;
    }

    /**
     * Xóa loại quân tư trang
     * 
     * @param int $id ID của loại quân tư trang
     * @return bool
     */
    public function deleteEquipmentType(int $id): bool
    {
        return MilitaryEquipmentType::findOrFail($id)->delete();
    }

    /**
     * Lấy tất cả phân phối quân tư trang theo năm
     * 
     * @param int|null $year Năm cần lấy dữ liệu, null để lấy tất cả
     * @return Collection<YearlyEquipmentDistribution>
     */
    public function getYearlyDistributions(?int $year = null): Collection
    {
        $query = YearlyEquipmentDistribution::with('equipmentType');

        if ($year) {
            $query->where('year', $year);
        }

        return $query->get();
    }

    /**
     * Tạo mới một phân phối quân tư trang
     * 
     * @param array $data Dữ liệu cho phân phối
     * @return YearlyEquipmentDistribution
     */
    public function createDistribution(array $data): YearlyEquipmentDistribution
    {
        return YearlyEquipmentDistribution::create($data);
    }

    /**
     * Cập nhật phân phối quân tư trang
     * 
     * @param int $id ID của phân phối
     * @param array $data Dữ liệu cập nhật
     * @return YearlyEquipmentDistribution
     */
    public function updateDistribution(int $id, array $data): YearlyEquipmentDistribution
    {
        $distribution = YearlyEquipmentDistribution::findOrFail($id);
        $distribution->update($data);
        return $distribution;
    }

    /**
     * Xóa phân phối quân tư trang
     * 
     * @param int $id ID của phân phối
     * @return bool
     */
    public function deleteDistribution(int $id): bool
    {
        return YearlyEquipmentDistribution::findOrFail($id)->delete();
    }

    /**
     * Tạo các biên nhận quân tư trang cho học viên
     * 
     * @param int $distributionId ID của phân phối
     * @param array $studentIds Danh sách ID học viên
     * @return int Số biên nhận đã tạo
     */
    public function createReceiptsForStudents(int $distributionId, array $studentIds): int
    {
        $distribution = YearlyEquipmentDistribution::findOrFail($distributionId);
        $existingReceiptStudentIds = StudentEquipmentReceipt::where('distribution_id', $distributionId)
            ->whereIn('user_id', $studentIds)
            ->pluck('user_id')
            ->toArray();

        $newStudentIds = array_diff($studentIds, $existingReceiptStudentIds);
        $receiptsToCreate = [];

        foreach ($newStudentIds as $studentId) {
            $receiptsToCreate[] = [
                'user_id' => $studentId,
                'distribution_id' => $distributionId,
                'received' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($receiptsToCreate)) {
            DB::table('student_equipment_receipts')->insert($receiptsToCreate);
        }

        return count($receiptsToCreate);
    }

    /**
     * Cập nhật trạng thái nhận quân tư trang của học viên
     * 
     * @param int $receiptId ID của biên nhận
     * @param bool $received Trạng thái đã nhận hay chưa
     * @param string|null $notes Ghi chú (nếu có)
     * @return StudentEquipmentReceipt
     */
    public function updateReceiptStatus(int $receiptId, bool $received, ?string $notes = null): StudentEquipmentReceipt
    {
        $receipt = StudentEquipmentReceipt::findOrFail($receiptId);

        $receipt->received = $received;
        if ($received) {
            $receipt->received_at = Carbon::now();
        } else {
            $receipt->received_at = null;
        }

        if ($notes !== null) {
            $receipt->notes = $notes;
        }

        $receipt->save();
        return $receipt;
    }

    /**
     * Lấy danh sách quân tư trang của học viên theo năm
     * 
     * @param int $studentId ID của học viên
     * @param int|null $year Năm cần lấy, null để lấy tất cả
     * @return Collection<StudentEquipmentReceipt>
     */
    public function getStudentEquipment(int $studentId, ?int $year = null): Collection
    {
        $query = StudentEquipmentReceipt::with(['distribution', 'distribution.equipmentType'])
            ->where('user_id', $studentId);

        if ($year) {
            $query->whereHas('distribution', function ($q) use ($year) {
                $q->where('year', $year);
            });
        }

        return $query->get();
    }

    /**
     * Lấy tất cả học viên chưa nhận đủ quân tư trang theo năm
     * 
     * @param int $year Năm cần kiểm tra
     * @return array
     */
    public function getStudentsWithPendingEquipment(int $year): array
    {
        $distributions = YearlyEquipmentDistribution::where('year', $year)
            ->with('equipmentType')
            ->get();

        if ($distributions->isEmpty()) {
            return [];
        }

        $distributionIds = $distributions->pluck('id')->toArray();

        // Lấy danh sách học viên với thông tin cơ bản
        $students = User::where('role', User::ROLE_STUDENT)
            ->with(['studentDetail'])
            ->get();

        $result = [];

        foreach ($students as $student) {
            // Lấy các biên nhận chưa nhận của học viên
            $pendingReceipts = StudentEquipmentReceipt::where('user_id', $student->id)
                ->whereIn('distribution_id', $distributionIds)
                ->where('received', false)
                ->with('distribution.equipmentType')
                ->get();

            if ($pendingReceipts->isNotEmpty()) {
                // Chỉ lấy thông tin cần thiết của học viên
                $studentInfo = [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ];

                // Thông tin chi tiết về quân tư trang chưa nhận
                $pendingEquipmentDetails = [];
                foreach ($pendingReceipts as $receipt) {
                    $pendingEquipmentDetails[] = [
                        'receipt_id' => $receipt->id,
                        'equipment_name' => $receipt->distribution->equipmentType->name,
                        'equipment_description' => $receipt->distribution->equipmentType->description,
                        'distribution_year' => $receipt->distribution->year,
                        'quantity' => $receipt->distribution->quantity,
                    ];
                }

                $result[] = [
                    'student' => $studentInfo,
                    'pending_count' => $pendingReceipts->count(),
                    'pending_equipment' => $pendingEquipmentDetails,
                ];
            }
        }

        return $result;
    }
}
