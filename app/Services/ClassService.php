<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\StudentClass;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class ClassService
{
    /**
     * Lấy danh sách tất cả các lớp
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllClasses()
    {
        $classes = ClassRoom::with('manager:id,name,email')->get();

        // Thêm số lượng học viên trong mỗi lớp
        foreach ($classes as $class) {
            $class->student_count = StudentClass::where('class_id', $class->id)
                ->count();
        }

        return $classes;
    }

    /**
     * Lấy thông tin chi tiết của một lớp
     *
     * @return ClassRoom
     *
     * @throws Exception
     */
    public function getClass(int $classId)
    {
        $class = ClassRoom::with([
            'manager:id,name,email,image', 
            'students:id,name,email,image'
        ])->find($classId);

        if (! $class) {
            throw new Exception('Không tìm thấy lớp', 422);
        }

        return $class;
    }

    /**
     * Tạo lớp mới
     *
     * @return ClassRoom
     *
     * @throws Exception
     */
    public function createClass(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                // Kiểm tra manager nếu có
                if (! empty($data['manager_id'])) {
                    $manager = User::where('id', $data['manager_id'])
                        ->where('role', User::ROLE_MANAGER)
                        ->first();

                    if (! $manager) {
                        throw new Exception('Quản lý không tồn tại hoặc không phải là manager', 422);
                    }

                    // Kiểm tra xem manager đã quản lý lớp nào chưa
                    $existingClass = ClassRoom::where('manager_id', $data['manager_id'])->first();
                    if ($existingClass) {
                        throw new Exception('Quản lý này đã được chỉ định cho lớp khác', 422);
                    }
                }

                // Tạo lớp mới
                $class = ClassRoom::create([
                    'name' => $data['name'],
                    'manager_id' => $data['manager_id'] ?? null,
                ]);

                // Nếu có manager, cập nhật manager_detail
                if (! empty($data['manager_id'])) {
                    $manager->managerDetail->update([
                        'management_unit' => $class->name,
                    ]);
                }

                return $class;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin lớp
     *
     * @return ClassRoom
     *
     * @throws Exception
     */
    public function updateClass(int $classId, array $data)
    {
        try {
            return DB::transaction(function () use ($classId, $data) {
                $class = ClassRoom::find($classId);
                if (! $class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }

                // Nếu thay đổi manager
                if (isset($data['manager_id']) && $class->manager_id != $data['manager_id']) {
                    // Kiểm tra manager mới
                    $newManager = null;
                    if ($data['manager_id']) {
                        $newManager = User::where('id', $data['manager_id'])
                            ->where('role', User::ROLE_MANAGER)
                            ->first();

                        if (! $newManager) {
                            throw new Exception('Quản lý không tồn tại hoặc không phải là manager', 422);
                        }

                        // Kiểm tra xem manager mới đã quản lý lớp nào chưa
                        $existingClass = ClassRoom::where('manager_id', $data['manager_id'])->first();
                        if ($existingClass && $existingClass->id != $classId) {
                            throw new Exception('Quản lý này đã được chỉ định cho lớp khác', 422);
                        }
                    }

                    // Cập nhật management_unit trong manager_detail
                    if ($class->manager_id) {
                        $oldManager = User::find($class->manager_id);
                        if ($oldManager && $oldManager->managerDetail) {
                            $oldManager->managerDetail->update([
                                'management_unit' => null,
                            ]);
                        }
                    }

                    if ($newManager) {
                        $newManager->managerDetail->update([
                            'management_unit' => $data['name'] ?? $class->name,
                        ]);
                    }
                }

                // Cập nhật thông tin lớp
                $class->update($data);

                return $class;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Xóa lớp
     *
     * @return bool
     *
     * @throws Exception
     */
    public function deleteClass(int $classId)
    {
        try {
            return DB::transaction(function () use ($classId) {
                $class = ClassRoom::find($classId);
                if (! $class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }

                // Kiểm tra xem lớp có học viên không
                $studentCount = StudentClass::where('class_id', $classId)->count();
                if ($studentCount > 0) {
                    throw new Exception('Không thể xóa lớp vì vẫn còn học viên trong lớp', 422);
                }

                // Xóa lớp
                return $class->delete();
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Thêm học viên vào lớp
     *
     * @return StudentClass
     *
     * @throws Exception
     */
    public function addStudentToClass(int $classId, int $studentId, array $data = [])
    {
        try {
            return DB::transaction(function () use ($classId, $studentId, $data) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (! $class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }

                // Kiểm tra học viên
                $student = User::where('id', $studentId)
                    ->where('role', User::ROLE_STUDENT)
                    ->first();

                if (! $student) {
                    throw new Exception('Không tìm thấy học viên hoặc người dùng không phải là học viên', 422);
                }

                // Kiểm tra xem học viên đã thuộc lớp nào chưa
                $existingClass = StudentClass::where('user_id', $studentId)->first();
                if ($existingClass) {
                    throw new Exception('Học viên đã thuộc về một lớp khác', 422);
                }

                // Kiểm tra nếu thêm lớp trưởng mới
                if (isset($data['role']) && $data['role'] === 'monitor') {
                    // Kiểm tra xem lớp đã có lớp trưởng chưa
                    $existingMonitor = StudentClass::where('class_id', $classId)
                        ->where('role', 'monitor')
                        ->first();

                    if ($existingMonitor) {
                        throw new Exception('Lớp đã có lớp trưởng', 422);
                    }
                }

                // Thêm học viên vào lớp
                $studentClass = StudentClass::create([
                    'user_id' => $studentId,
                    'class_id' => $classId,
                    'role' => $data['role'] ?? 'student',
                    'status' => $data['status'] ?? 'active',
                    'reason' => $data['reason'] ?? null,
                    'note' => $data['note'] ?? null,
                ]);

                return $studentClass;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Thêm nhiều học viên vào lớp theo bulk
     *
     * @param int $classId
     * @param array $studentIds Mảng chứa IDs của học viên
     * @param array $data Thông tin bổ sung
     * @return array Kết quả với thông tin thành công/thất bại cho từng học viên
     * @throws Exception
     */
    public function bulkAddStudentsToClass(int $classId, array $studentIds, array $data = [])
    {
        // Kiểm tra lớp tồn tại
        $class = ClassRoom::find($classId);
        if (!$class) {
            throw new Exception('Không tìm thấy lớp', 422);
        }

        $results = [
            'success' => [],
            'failed' => []
        ];

        // Xử lý trong một transaction
        DB::beginTransaction();
        try {
            foreach ($studentIds as $studentId) {
                try {
                    // Kiểm tra học viên
                    $student = User::where('id', $studentId)
                        ->where('role', User::ROLE_STUDENT)
                        ->first();

                    if (!$student) {
                        throw new Exception('Không tìm thấy học viên hoặc người dùng không phải là học viên');
                    }

                    // Kiểm tra xem học viên đã thuộc lớp nào chưa
                    $existingClass = StudentClass::where('user_id', $studentId)->first();
                    if ($existingClass) {
                        throw new Exception('Học viên đã thuộc về một lớp khác');
                    }

                    // Thêm học viên vào lớp
                    $studentClass = StudentClass::create([
                        'user_id' => $studentId,
                        'class_id' => $classId,
                        'role' => $data['role'] ?? 'student',
                        'status' => $data['status'] ?? 'active',
                        'reason' => $data['reason'] ?? null,
                        'note' => $data['note'] ?? null,
                    ]);

                    $results['success'][] = [
                        'id' => $studentId,
                        'name' => $student->name,
                        'message' => 'Đã thêm thành công vào lớp'
                    ];
                } catch (Exception $e) {
                    // Ghi lại lỗi cho học viên này nhưng vẫn tiếp tục với học viên khác
                    $student = User::find($studentId);
                    $results['failed'][] = [
                        'id' => $studentId,
                        'name' => $student ? $student->name : 'Unknown',
                        'message' => $e->getMessage()
                    ];
                }
            }

            DB::commit();
            return $results;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin học viên trong lớp
     *
     * @return StudentClass
     *
     * @throws Exception
     */
    public function updateStudentClass(int $classId, int $studentId, array $data)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId, $data) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (! $class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }

                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                    ->where('user_id', $studentId)
                    ->first();

                if (! $studentClass) {
                    throw new Exception('Học viên không thuộc lớp này', 422);
                }


                // Kiểm tra nếu trạng thái tạm hoãn mà không có lý do
                if (isset($data['status']) && $data['status'] === 'suspended' && empty($data['reason'])) {
                    throw new Exception('Cần cung cấp lý do khi tạm hoãn học viên', 422);
                }

                // Cập nhật thông tin (chỉ nhận status, reason, note)
                $studentClass->update([
                    'status' => $data['status'] ?? $studentClass->status,
                    'reason' => $data['reason'] ?? $studentClass->reason,
                    'note' => $data['note'] ?? $studentClass->note,
                ]);

                return $studentClass;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Xóa học viên khỏi lớp
     *
     * @return bool
     *
     * @throws Exception
     */
    public function removeStudentFromClass(int $classId, int $studentId)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (! $class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }

                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                    ->where('user_id', $studentId)
                    ->first();

                if (! $studentClass) {
                    throw new Exception('Học viên không thuộc lớp này', 422);
                }

                // Xóa học viên khỏi lớp
                return $studentClass->delete();
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Lấy thông tin lớp của một manager
     *
     * @return ClassRoom
     *
     * @throws Exception
     */
    public function getManagerClass(int $managerId)
    {
        $class = ClassRoom::with([
            'manager:id,name,email,image', 
            'students:id,name,email,image'
        ])->where('manager_id', $managerId)
            ->first();

        if (! $class) {
            throw new Exception('Không tìm thấy lớp', 422);
        }

        return $class;
    }

    /**
     * Lấy thông tin chi tiết của học viên trong lớp
     *
     * @return array
     *
     * @throws Exception
     */
    public function getStudentClassDetail(int $classId, int $studentId)
    {
        $studentClass = StudentClass::where('class_id', $classId)
            ->where('user_id', $studentId)
            ->with('student:id,name,email,image')
            ->first();

        if (! $studentClass) {
            throw new Exception('Không tìm thấy học viên trong lớp này', 422);
        }

        return $studentClass->toArray();
    }

    /**
     * Chỉ định lớp trưởng
     *
     * @return StudentClass
     *
     * @throws Exception
     */
    public function assignMonitor(int $classId, int $studentId)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (! $class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }

                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                    ->where('user_id', $studentId)
                    ->first();

                if (! $studentClass) {
                    throw new Exception('Học viên không thuộc lớp này', 422);
                }

                // Kiểm tra xem lớp đã có lớp trưởng chưa
                $existingMonitor = StudentClass::where('class_id', $classId)
                    ->where('role', 'monitor')
                    ->first();

                if ($existingMonitor) {
                    throw new Exception('Lớp đang có lớp trưởng, cần chỉ định về học viên bình thường trước khi chỉ định lớp trưởng mới', 422);
                }

                // Chỉ định lớp trưởng mới
                $studentClass->update(['role' => 'monitor']);

                return $studentClass;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Chỉ định lớp phó
     *
     * @return StudentClass
     *
     * @throws Exception
     */
    public function assignViceMonitor(int $classId, int $studentId)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (! $class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }

                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                    ->where('user_id', $studentId)
                    ->first();

                if (! $studentClass) {
                    throw new Exception('Học viên không thuộc lớp này', 422);
                }

                // Không cho phép lớp trưởng kiêm lớp phó
                if ($studentClass->role === 'monitor') {
                    throw new Exception('Lớp trưởng không thể kiêm lớp phó', 422);
                }

                // Chỉ định lớp phó
                $studentClass->update(['role' => 'vice_monitor']);

                return $studentClass;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Chỉ định học viên làm thành viên thường
     *
     * @return StudentClass
     *
     * @throws Exception
     */
    public function assignStudent(int $classId, int $studentId)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (! $class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }

                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                    ->where('user_id', $studentId)
                    ->first();

                if (! $studentClass) {
                    throw new Exception('Học viên không thuộc lớp này', 422);
                }

                // Chỉ định làm thành viên thường
                $studentClass->update(['role' => 'student']);

                return $studentClass;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }
}
