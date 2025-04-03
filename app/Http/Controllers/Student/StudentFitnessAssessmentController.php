<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentFitnessRecordResource;
use App\Models\StudentFitnessRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentFitnessAssessmentController extends Controller
{
    /**
     * Lấy danh sách kết quả kiểm tra thể lực của student đang đăng nhập.
     * Fetches the fitness assessment results for the currently logged-in student.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse Trả về danh sách kết quả dưới dạng JSON, sử dụng StudentFitnessRecordResource. Returns a list of results as JSON using StudentFitnessRecordResource.
     */
    public function getMyAssessments(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Lấy danh sách các bản ghi thể lực của user
        // Eager load related fitness test and assessment session data
        $fitnessRecords = StudentFitnessRecord::where('user_id', $user->id)
                                              ->with(['fitnessTest', 'assessmentSession'])
                                              ->orderBy('created_at', 'desc') // Sắp xếp theo ngày ghi nhận mới nhất
                                              ->get();

        // Trả về danh sách kết quả sử dụng StudentFitnessRecordResource collection
        // Return the list of results using StudentFitnessRecordResource collection
        return response()->json([
            'status' => 'success',
            'data' => StudentFitnessRecordResource::collection($fitnessRecords)
        ]);
    }
}
