<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ManagerUserImageUpdateRequest;
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
     * Xem chi tiết thông tin student (manager).
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
     * Cập nhật thông tin chi tiết student (manager).
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
                    // Father information
                    'father_name' => 'nullable|string|max:255',
                    'father_birth_year' => 'nullable|integer|min:1900|max:2100',
                    'father_phone_number' => 'nullable|string|max:20',
                    'father_place_of_origin' => 'nullable|string|max:255',
                    'father_occupation' => 'nullable|string|max:255',
                    // Mother information
                    'mother_name' => 'nullable|string|max:255',
                    'mother_birth_year' => 'nullable|integer|min:1900|max:2100',
                    'mother_phone_number' => 'nullable|string|max:20',
                    'mother_place_of_origin' => 'nullable|string|max:255',
                    'mother_occupation' => 'nullable|string|max:255',
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

    /**
     * Cập nhật ảnh đại diện cho student (manager).
     */
    public function updateImage(ManagerUserImageUpdateRequest $request, $userId): JsonResponse
    {
        return $this->executeService(
            function () use ($request, $userId) {
                $user = User::findOrFail($userId);
                if (!$user->isStudent()) {
                    return $this->sendError('User is not a student', [], 404);
                }

                $user->image = $request->image;
                $user->save();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'image' => $user->image,
                ];
            },
            'Cập nhật ảnh đại diện học viên thành công'
        );
    }
}
