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
                                               ->where('status', 'active')
                                               ->count();
        }
        
        return $classes;
    }
    
    /**
     * Lấy thông tin chi tiết của một lớp
     *
     * @param int $classId
     * @return array
     * @throws Exception
     */
    public function getClass(int $classId)
    {
        $class = ClassRoom::with(['manager:id,name,email', 'students:id,name,email'])
                           ->find($classId);
                           
        if (!$class) {
            throw new Exception('Không tìm thấy lớp', 422);
        }
        
        // Sử dụng các phương thức model thay vì truy vấn lại
        $monitor = $class->monitor();
        $viceMonitors = $class->viceMonitors();
        
        $result = $class->toArray();
        $result['monitor_id'] = $monitor ? $monitor->id : null;
        $result['vice_monitor_ids'] = $viceMonitors->pluck('id')->toArray();
        
        return $result;
    }
    
    /**
     * Tạo lớp mới
     *
     * @param array $data
     * @return ClassRoom
     * @throws Exception
     */
    public function createClass(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                // Kiểm tra manager nếu có
                if (!empty($data['manager_id'])) {
                    $manager = User::where('id', $data['manager_id'])
                                    ->where('role', User::ROLE_MANAGER)
                                    ->first();
                    
                    if (!$manager) {
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
                    'manager_id' => $data['manager_id'] ?? null
                ]);
                
                // Nếu có manager, cập nhật manager_detail
                if (!empty($data['manager_id'])) {
                    $manager->managerDetail->update([
                        'management_unit' => $class->name
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
     * @param int $classId
     * @param array $data
     * @return ClassRoom
     * @throws Exception
     */
    public function updateClass(int $classId, array $data)
    {
        try {
            return DB::transaction(function () use ($classId, $data) {
                $class = ClassRoom::find($classId);
                if (!$class) {
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
                                          
                        if (!$newManager) {
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
                                'management_unit' => null
                            ]);
                        }
                    }
                    
                    if ($newManager) {
                        $newManager->managerDetail->update([
                            'management_unit' => $data['name'] ?? $class->name
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
     * @param int $classId
     * @return bool
     * @throws Exception
     */
    public function deleteClass(int $classId)
    {
        try {
            return DB::transaction(function () use ($classId) {
                $class = ClassRoom::find($classId);
                if (!$class) {
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
     * @param int $classId
     * @param int $studentId
     * @param array $data
     * @return StudentClass
     * @throws Exception
     */
    public function addStudentToClass(int $classId, int $studentId, array $data = [])
    {
        try {
            return DB::transaction(function () use ($classId, $studentId, $data) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (!$class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }
                
                // Kiểm tra học viên
                $student = User::where('id', $studentId)
                                ->where('role', User::ROLE_STUDENT)
                                ->first();
                                
                if (!$student) {
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
                    'note' => $data['note'] ?? null
                ]);
                
                return $studentClass;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Cập nhật thông tin học viên trong lớp
     *
     * @param int $classId
     * @param int $studentId
     * @param array $data
     * @return StudentClass
     * @throws Exception
     */
    public function updateStudentClass(int $classId, int $studentId, array $data)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId, $data) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (!$class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }
                
                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                                           ->where('user_id', $studentId)
                                           ->first();
                                           
                if (!$studentClass) {
                    throw new Exception('Học viên không thuộc lớp này', 422);
                }
                
                // Kiểm tra nếu thay đổi vai trò thành lớp trưởng
                if (isset($data['role']) && $data['role'] === 'monitor' && $studentClass->role !== 'monitor') {
                    // Kiểm tra xem lớp đã có lớp trưởng chưa
                    $existingMonitor = StudentClass::where('class_id', $classId)
                                                   ->where('role', 'monitor')
                                                   ->where('user_id', '!=', $studentId)
                                                   ->first();
                                                   
                    if ($existingMonitor) {
                        throw new Exception('Lớp đã có lớp trưởng', 422);
                    }
                }
                
                // Kiểm tra nếu trạng thái tạm hoãn mà không có lý do
                if (isset($data['status']) && $data['status'] === 'suspended' && empty($data['reason'])) {
                    throw new Exception('Cần cung cấp lý do khi tạm hoãn học viên', 422);
                }
                
                // Cập nhật thông tin
                $studentClass->update($data);
                
                return $studentClass;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Xóa học viên khỏi lớp
     *
     * @param int $classId
     * @param int $studentId
     * @return bool
     * @throws Exception
     */
    public function removeStudentFromClass(int $classId, int $studentId)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (!$class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }
                
                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                                           ->where('user_id', $studentId)
                                           ->first();
                                           
                if (!$studentClass) {
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
     * @param int $managerId
     * @return array
     * @throws Exception
     */
    public function getManagerClass(int $managerId)
    {
        $class = ClassRoom::with(['manager:id,name,email', 'students:id,name,email'])
                         ->where('manager_id', $managerId)
                         ->first();
                         
        if (!$class) {
            throw new Exception('Không tìm thấy lớp', 422);
        }
        
        // Sử dụng các phương thức model thay vì truy vấn lại
        $monitor = $class->monitor();
        $viceMonitors = $class->viceMonitors();
        
        $result = $class->toArray();
        $result['monitor_id'] = $monitor ? $monitor->id : null;
        $result['vice_monitor_ids'] = $viceMonitors->pluck('id')->toArray();
        
        return $result;
    }
    
    /**
     * Lấy thông tin chi tiết của học viên trong lớp
     *
     * @param int $classId
     * @param int $studentId
     * @return array
     * @throws Exception
     */
    public function getStudentClassDetail(int $classId, int $studentId)
    {
        $studentClass = StudentClass::where('class_id', $classId)
                                    ->where('user_id', $studentId)
                                    ->with('student:id,name,email')
                                    ->first();
                                    
        if (!$studentClass) {
            throw new Exception('Không tìm thấy học viên trong lớp này', 422);
        }
        
        return $studentClass->toArray();
    }
    
    /**
     * Chỉ định lớp trưởng
     *
     * @param int $classId
     * @param int $studentId
     * @return StudentClass
     * @throws Exception
     */
    public function assignMonitor(int $classId, int $studentId)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (!$class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }
                
                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                                           ->where('user_id', $studentId)
                                           ->first();
                                           
                if (!$studentClass) {
                    throw new Exception('Học viên không thuộc lớp này', 422);
                }
                
                // Hủy bỏ lớp trưởng hiện tại
                StudentClass::where('class_id', $classId)
                           ->where('role', 'monitor')
                           ->update(['role' => 'student']);
                           
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
     * @param int $classId
     * @param int $studentId
     * @return StudentClass
     * @throws Exception
     */
    public function assignViceMonitor(int $classId, int $studentId)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (!$class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }
                
                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                                           ->where('user_id', $studentId)
                                           ->first();
                                           
                if (!$studentClass) {
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
     * @param int $classId
     * @param int $studentId
     * @return StudentClass
     * @throws Exception
     */
    public function assignStudent(int $classId, int $studentId)
    {
        try {
            return DB::transaction(function () use ($classId, $studentId) {
                // Kiểm tra lớp tồn tại
                $class = ClassRoom::find($classId);
                if (!$class) {
                    throw new Exception('Không tìm thấy lớp', 422);
                }
                
                // Kiểm tra học viên trong lớp
                $studentClass = StudentClass::where('class_id', $classId)
                                           ->where('user_id', $studentId)
                                           ->first();
                                           
                if (!$studentClass) {
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