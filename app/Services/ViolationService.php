<?php

namespace App\Services;

use App\Models\User;
use App\Models\ViolationRecord;
use Exception;
use Illuminate\Support\Facades\DB;

class ViolationService
{
    /**
     * Lấy danh sách vi phạm của một học viên
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws Exception
     */
    public function getStudentViolations(int $studentId)
    {
        try {
            // Kiểm tra học viên tồn tại
            $student = User::find($studentId);
            if (! $student) {
                throw new Exception('Không tìm thấy học viên', 422);
            }

            // Kiểm tra xem có phải học viên không
            if (! $student->isStudent()) {
                throw new Exception('Người dùng được chọn không phải là học viên', 422);
            }

            // Lấy danh sách vi phạm, sắp xếp theo thời gian tạo mới nhất
            $violations = ViolationRecord::with(['manager' => function ($query) {
                $query->select('id', 'name', 'email');
            }])
                ->where('student_id', $studentId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Thêm trường is_editable, student_name và manager info cho mỗi vi phạm
            $violations->each(function ($violation) use ($student) {
                $violation->is_editable = $violation->isEditable();
                $violation->student_name = $student->name;
                
                // Thêm thông tin manager vào response
                if ($violation->manager) {
                    $violation->manager_name = $violation->manager->name;
                    $violation->manager_email = $violation->manager->email;
                }
            });

            return $violations;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Tạo mới một bản ghi vi phạm
     *
     * @param  array  $data  Dữ liệu vi phạm
     * @param  int  $managerId  ID của manager
     * @return ViolationRecord
     *
     * @throws Exception
     */
    public function createViolation(array $data, int $managerId)
    {
        try {
            return DB::transaction(function () use ($data, $managerId) {
                // Kiểm tra học viên tồn tại
                $student = User::find($data['student_id']);
                if (! $student) {
                    throw new Exception('Không tìm thấy học viên', 422);
                }

                // Kiểm tra xem có phải học viên không
                if (! $student->isStudent()) {
                    throw new Exception('Người dùng được chọn không phải là học viên', 422);
                }

                // Tạo bản ghi vi phạm
                $violation = ViolationRecord::create([
                    'student_id' => $data['student_id'],
                    'manager_id' => $managerId,
                    'violation_name' => $data['violation_name'],
                    'violation_date' => $data['violation_date'],
                ]);

                // Load thông tin manager để trả về cùng với violation
                $manager = User::select('id', 'name', 'email')->find($managerId);
                $violation->manager_name = $manager->name;
                $violation->manager_email = $manager->email;

                return $violation;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin vi phạm
     *
     * @param  int  $violationId  ID của vi phạm
     * @param  array  $data  Dữ liệu cập nhật
     * @param  int  $managerId  ID của manager
     * @return ViolationRecord
     *
     * @throws Exception
     */
    public function updateViolation(int $violationId, array $data, int $managerId)
    {
        try {
            return DB::transaction(function () use ($violationId, $data, $managerId) {
                // Tìm vi phạm
                $violation = ViolationRecord::find($violationId);
                if (! $violation) {
                    throw new Exception('Không tìm thấy bản ghi vi phạm', 422);
                }

                // Kiểm tra xem manager có quyền cập nhật vi phạm này không
                if ($violation->manager_id !== $managerId) {
                    throw new Exception('Bạn không có quyền cập nhật vi phạm này', 403);
                }

                // Kiểm tra thời gian chỉnh sửa
                if (! $violation->isEditable()) {
                    throw new Exception('Không thể chỉnh sửa vi phạm sau 1 ngày', 422);
                }

                // Cập nhật thông tin
                $violation->update([
                    'violation_name' => $data['violation_name'],
                    'violation_date' => $data['violation_date'],
                ]);

                // Load thông tin manager để trả về cùng với violation
                $violation->load(['manager' => function ($query) {
                    $query->select('id', 'name', 'email');
                }]);

                // Make sure manager is explicitly added to the response
                $manager = User::select('id', 'name', 'email')->find($managerId);
                $violation->manager_name = $manager->name;
                $violation->manager_email = $manager->email;

                return $violation;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Xóa một bản ghi vi phạm
     *
     * @param  int  $violationId  ID của vi phạm
     * @param  int  $managerId  ID của manager
     * @return bool
     *
     * @throws Exception
     */
    public function deleteViolation(int $violationId, int $managerId)
    {
        try {
            return DB::transaction(function () use ($violationId, $managerId) {
                // Tìm vi phạm
                $violation = ViolationRecord::find($violationId);
                if (! $violation) {
                    throw new Exception('Không tìm thấy bản ghi vi phạm', 422);
                }

                // Kiểm tra xem manager có quyền xóa vi phạm này không
                if ($violation->manager_id !== $managerId) {
                    throw new Exception('Bạn không có quyền xóa vi phạm này', 403);
                }

                // Kiểm tra thời gian chỉnh sửa
                if (! $violation->isEditable()) {
                    throw new Exception('Không thể xóa vi phạm sau 1 ngày', 422);
                }

                // Xóa vi phạm
                $result = $violation->delete();

                return $result;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }
}
