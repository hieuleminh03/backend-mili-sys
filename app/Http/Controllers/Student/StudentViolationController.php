<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ViolationRecordResource;
use App\Models\ViolationRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentViolationController extends Controller
{
    /**
     * Lấy danh sách các vi phạm của student đang đăng nhập.
     * Fetches the violation records for the currently logged-in student.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse Trả về danh sách vi phạm dưới dạng JSON, sử dụng ViolationRecordResource. Returns a list of violations as JSON using ViolationRecordResource.
     */
    public function getMyViolations(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Lấy danh sách các bản ghi vi phạm của user
        // Eager load related manager data
        $violationRecords = ViolationRecord::where('student_id', $user->id)
                                           ->with('manager') // Eager load manager info
                                           ->orderBy('violation_date', 'desc') // Sắp xếp theo ngày vi phạm mới nhất
                                           ->get();

        // Trả về danh sách vi phạm sử dụng ViolationRecordResource collection
        // Return the list of violations using ViolationRecordResource collection
        return response()->json([
            'status' => 'success',
            'data' => ViolationRecordResource::collection($violationRecords)
        ]);
    }
}
