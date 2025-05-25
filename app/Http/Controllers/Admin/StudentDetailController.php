<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\StudentDetailResource;
use App\Http\Resources\UserResource;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentDetailController extends BaseController
{
    /**
     * Xem chi tiết thông tin student (admin).
     */
    public function show($userId): JsonResponse
    {
        return $this->executeService(
            function () use ($userId) {
                $user = User::with(['studentDetail', 'studentClass'])->findOrFail($userId);
                if (!$user->isStudent()) {
                    return $this->sendError('User is not a student', [], 404);
                }
                return new UserResource($user);
            },
            'Lấy thông tin chi tiết học viên thành công'
        );
    }

    /**
     * Cập nhật thông tin chi tiết student (admin).
     */
    public function update(Request $request, $userId): JsonResponse
    {
        return $this->executeService(
            function () use ($request, $userId) {
                $user = User::with('studentDetail')->findOrFail($userId);
                if (!$user->isStudent()) {
                    return $this->sendError('User is not a student', [], 404);
                }

                $validator = Validator::make($request->all(), [
                    'date_of_birth' => 'nullable|date',
                    'rank' => 'nullable|string|max:255',
                    'place_of_origin' => 'nullable|string|max:255',
                    'working_unit' => 'nullable|string|max:255',
                    'year_of_study' => 'nullable|integer|min:1',
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

                $studentDetail = $user->studentDetail;
                if (!$studentDetail) {
                    $studentDetail = new StudentDetail(['user_id' => $user->id]);
                }

                $studentDetail->fill($request->all());
                $studentDetail->save();

                return new StudentDetailResource($studentDetail);
            },
            'Cập nhật thông tin chi tiết học viên thành công'
        );
    }
}
