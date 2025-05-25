<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Http\Resources\StudentDetailResource;
use App\Http\Resources\UserResource;
use App\Models\StudentDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentProfileController extends BaseController
{
    /**
     * Lấy thông tin profile của student đang đăng nhập.
     * Fetches the profile information of the currently logged-in student.
     */
    public function getProfile(): JsonResponse
    {
        return $this->executeService(
            function () {
                $user = auth()->user()->load(['studentDetail', 'studentClass']);
                
                return new UserResource($user);
            },
            'Lấy thông tin profile thành công'
        );
    }
    
    /**
     * Cập nhật thông tin chi tiết của student đang đăng nhập.
     * Updates the detailed information of the currently logged-in student.
     */
    public function updateStudentDetail(Request $request): JsonResponse
    {
        return $this->executeService(
            function () use ($request) {
                $user = auth()->user();
                
                // Validate input
                $validator = Validator::make($request->all(), [
                    'date_of_birth' => 'nullable|date',
                    'rank' => 'nullable|string|max:255',
                    'place_of_origin' => 'nullable|string|max:255',
                    'working_unit' => 'nullable|string|max:255',
                    'year_of_study' => 'nullable|integer|min:1|max:10',
                    'political_status' => 'nullable|string|in:party_member,youth_union_member,none',
                    'phone_number' => 'nullable|string|max:20',
                    'permanent_residence' => 'nullable|string',
                    'parents_name' => 'nullable|string|max:255',
                    'parents_birth_year' => 'nullable|integer|min:1900|max:2100',
                    'parents_phone_number' => 'nullable|string|max:20',
                    'parents_place_of_origin' => 'nullable|string|max:255',
                    'parents_occupation' => 'nullable|string|max:255',
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Create or update student detail
                $studentDetail = $user->studentDetail;
                if (!$studentDetail) {
                    $studentDetail = new StudentDetail(['user_id' => $user->id]);
                }
                
                $studentDetail->fill($request->all());
                $studentDetail->save();
                
                return new StudentDetailResource($studentDetail);
            },
            'Cập nhật thông tin chi tiết thành công'
        );
    }
}
