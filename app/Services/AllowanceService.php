<?php
namespace App\Services;

use App\Models\MonthlyAllowance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AllowanceService
{
    /**
     * Lấy danh sách phụ cấp theo tháng/năm
     *
     * @param int|null $month Tháng cần lấy, null để lấy tất cả
     * @param int|null $year Năm cần lấy, null để lấy tất cả
     * @return Collection<MonthlyAllowance>
     */
    public function getAllowances(?int $month = null, ?int $year = null): Collection
    {
        $query = MonthlyAllowance::with('student');

        if ($month) {
            $query->where('month', $month);
        }

        if ($year) {
            $query->where('year', $year);
        }

        return $query->get();
    }

    /**
     * Tạo mới một phụ cấp hàng tháng
     *
     * @param array $data Dữ liệu cho phụ cấp
     * @return MonthlyAllowance
     */
    public function createAllowance(array $data): MonthlyAllowance
    {
        return MonthlyAllowance::create($data);
    }

    /**
     * Tạo nhiều phụ cấp hàng tháng cùng lúc
     *
     * @param array $studentIds Danh sách ID học viên
     * @param int $month Tháng của phụ cấp
     * @param int $year Năm của phụ cấp
     * @param float $amount Số tiền phụ cấp
     * @return array Mảng chứa số lượng và danh sách ID phụ cấp đã tạo
     */
    public function createBulkAllowances(array $studentIds, int $month, int $year, float $amount): array
    {
        $existingAllowances = MonthlyAllowance::whereIn('user_id', $studentIds)
            ->where('month', $month)
            ->where('year', $year)
            ->pluck('user_id')
            ->toArray();

        $newStudentIds      = array_diff($studentIds, $existingAllowances);
        $allowancesToCreate = [];

        foreach ($newStudentIds as $studentId) {
            $allowancesToCreate[] = [
                'user_id'    => $studentId,
                'month'      => $month,
                'year'       => $year,
                'amount'     => $amount,
                'received'   => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $allowanceIds = [];
        
        if (! empty($allowancesToCreate)) {
            // Insert the allowances
            DB::table('monthly_allowances')->insert($allowancesToCreate);
            
            // Get the IDs of the newly created allowances
            $allowanceIds = MonthlyAllowance::whereIn('user_id', $newStudentIds)
                ->where('month', $month)
                ->where('year', $year)
                ->pluck('id')
                ->toArray();
        }

        return [
            'count' => count($allowancesToCreate),
            'allowance_ids' => $allowanceIds
        ];
    }

    /**
     * Cập nhật phụ cấp hàng tháng
     *
     * @param int $id ID của phụ cấp
     * @param array $data Dữ liệu cập nhật
     * @return MonthlyAllowance
     */
    public function updateAllowance(int $id, array $data): MonthlyAllowance
    {
        $allowance = MonthlyAllowance::findOrFail($id);

        if (array_key_exists('received', $data)) {
            $currentReceived = $allowance->received;
            $newReceived     = $data['received'];

            if ($newReceived) {
                // Luôn cập nhật received_at khi nhận
                $data['received_at'] = now();
            } else if ($currentReceived !== $newReceived) {
                // Chỉ xóa received_at khi chuyển từ đã nhận sang chưa nhận
                $data['received_at'] = null;
            }
        }

        $allowance->update($data);
        return $allowance;
    }

    /**
     * Xóa phụ cấp hàng tháng
     *
     * @param int $id ID của phụ cấp
     * @return bool
     */
    public function deleteAllowance(int $id): bool
    {
        return MonthlyAllowance::findOrFail($id)->delete();
    }

    /**
     * Cập nhật trạng thái nhận phụ cấp của học viên
     *
     * @param int $allowanceId ID của phụ cấp
     * @param bool $received Trạng thái đã nhận hay chưa
     * @param string|null $notes Ghi chú (nếu có)
     * @return MonthlyAllowance
     */
    public function updateAllowanceStatus(int $allowanceId, bool $received, ?string $notes = null): MonthlyAllowance
    {
        $allowance = MonthlyAllowance::findOrFail($allowanceId);

        $allowance->received = $received;
        if ($received) {
            $allowance->received_at = Carbon::now();
        } else {
            $allowance->received_at = null;
        }

        if ($notes !== null) {
            $allowance->notes = $notes;
        }

        $allowance->save();
        return $allowance;
    }

    /**
     * Lấy phụ cấp của một học viên
     *
     * @param int $studentId ID của học viên
     * @param int|null $month Tháng cần lấy, null để lấy tất cả
     * @param int|null $year Năm cần lấy, null để lấy tất cả
     * @return Collection<MonthlyAllowance>
     */
    public function getStudentAllowances(int $studentId, ?int $month = null, ?int $year = null): Collection
    {
        $query = MonthlyAllowance::where('user_id', $studentId);

        if ($month) {
            $query->where('month', $month);
        }

        if ($year) {
            $query->where('year', $year);
        }

        return $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
    }

    /**
     * Lấy danh sách học viên chưa nhận phụ cấp theo tháng/năm
     *
     * @param int|null $month Tháng cần kiểm tra, null để lấy tất cả
     * @param int|null $year Năm cần kiểm tra, null để lấy tất cả
     * @return array
     */
    public function getStudentsWithPendingAllowances(?int $month = null, ?int $year = null): array
    {
        $students = User::where('role', User::ROLE_STUDENT)
            ->with(['studentClass.classRoom'])
            ->get();

        $result = [];

        foreach ($students as $student) {
            $query = MonthlyAllowance::where('user_id', $student->id)
                ->where('received', false);

            if ($month) {
                $query->where('month', $month);
            }
            if ($year) {
                $query->where('year', $year);
            }

            $pendingAllowance = $query->first();

            if ($pendingAllowance) {
                $result[] = [
                    'user_id'   => $student->id,
                    'name'      => $student->name,
                    'email'     => $student->email,
                    'allowance' => [
                        "id"         => $pendingAllowance->id,
                        'month'      => $pendingAllowance->month,
                        'year'       => $pendingAllowance->year,
                        'notes'      => $pendingAllowance->notes,
                        'amount'     => $pendingAllowance->amount,
                        'created_at' => $pendingAllowance->created_at,
                    ],
                ];
            }
        }

        return $result;
    }
}
