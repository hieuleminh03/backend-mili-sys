<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Http\Resources\StudentFitnessRecordResource;
use App\Models\StudentFitnessRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentFitnessAssessmentController extends BaseController
{
    /**
     * Lấy danh sách kết quả kiểm tra thể lực của student đang đăng nhập.
     * Fetches the fitness assessment results for the currently logged-in student.
     *
     * @return JsonResponse
     */
    public function getMyAssessments(): JsonResponse
    {
        $userId = auth()->id();

        return $this->executeService(
            function () use ($userId) {
                // Lấy danh sách các bản ghi thể lực của user
                $fitnessRecords = StudentFitnessRecord::where('user_id', $userId)
                    ->with(['fitnessTest', 'assessmentSession'])
                    ->orderBy('created_at', 'desc') // Sắp xếp theo ngày ghi nhận mới nhất
                    ->get();

                return StudentFitnessRecordResource::collection($fitnessRecords);
            },
            'Lấy danh sách kết quả kiểm tra thể lực thành công'
        );
    }
}
