<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Models\StudentClass;
use Illuminate\Http\JsonResponse;

class StudentClassController extends BaseController
{
    /**
     * Lấy thông tin lớp mà học viên thuộc về
     */
    public function getMyClass(): JsonResponse
    {
        $studentId = auth()->id();

        return $this->executeService(
            function () use ($studentId) {
                // Lấy thông tin lớp của học viên
                $studentClass = StudentClass::where('user_id', $studentId)
                    ->with(['classRoom', 'classRoom.manager:id,name,email,image'])
                    ->first();

                if (! $studentClass) {
                    return [
                        'data' => null,
                        'message' => 'Bạn chưa được phân vào lớp nào',
                    ];
                }

                // Lấy thông tin về lớp trưởng và lớp phó
                $classId = $studentClass->class_id;
                $classmates = StudentClass::where('class_id', $classId)
                    ->with('student:id,name,email,image')
                    ->get();

                $monitor = $classmates->where('role', 'monitor')->first();
                $viceMonitors = $classmates->where('role', 'vice_monitor')->values();

                // Tạo kết quả
                return [
                    'class' => $studentClass->classRoom,
                    'role' => $studentClass->role,
                    'status' => $studentClass->status,
                    'reason' => $studentClass->reason,
                    'monitor' => $monitor ? $monitor->student : null,
                    'vice_monitors' => $viceMonitors->map(function ($vm) {
                        return $vm->student;
                    }),
                    'classmates_count' => $classmates->count(),
                ];
            },
            'Lấy thông tin lớp thành công'
        );
    }

    /**
     * Lấy danh sách học viên trong lớp
     */
    public function getClassmates(): JsonResponse
    {
        $studentId = auth()->id();

        return $this->executeService(
            function () use ($studentId) {
                // Lấy thông tin lớp của học viên
                $studentClass = StudentClass::where('user_id', $studentId)->first();

                if (! $studentClass) {
                    throw new \Exception('Bạn chưa được phân vào lớp nào', 422);
                }

                // Kiểm tra trạng thái của học viên
                if ($studentClass->status !== 'active') {
                    throw new \Exception('Bạn đang ở trạng thái tạm hoãn và không thể xem thông tin lớp', 403);
                }

                // Lấy danh sách bạn học
                $classId = $studentClass->class_id;

                return StudentClass::where('class_id', $classId)
                    ->with('student:id,name,email,image')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->student->id,
                            'name' => $item->student->name,
                            'email' => $item->student->email,
                            'image' => $item->student->image,
                            'role' => $item->role,
                            'status' => $item->status,
                        ];
                    });
            },
            'Lấy danh sách học viên trong lớp thành công'
        );
    }
}
