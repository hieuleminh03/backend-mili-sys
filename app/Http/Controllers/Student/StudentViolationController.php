<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ViolationRecordResource;
use App\Models\ViolationRecord;
use Illuminate\Http\JsonResponse;

class StudentViolationController extends BaseController
{
    /**
     * Lấy danh sách các vi phạm của student đang đăng nhập.
     * Fetches the violation records for the currently logged-in student.
     */
    public function getMyViolations(): JsonResponse
    {
        $userId = auth()->id();

        return $this->executeService(
            function () use ($userId) {
                // Lấy danh sách các bản ghi vi phạm của user
                $violationRecords = ViolationRecord::where('student_id', $userId)
                    ->with('manager') // Eager load manager info
                    ->orderBy('violation_date', 'desc') // Sắp xếp theo ngày vi phạm mới nhất
                    ->get();

                return ViolationRecordResource::collection($violationRecords);
            },
            'Lấy danh sách vi phạm thành công'
        );
    }
}
