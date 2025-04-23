<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\BaseController;
use App\Services\ManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ManagerProfileController extends BaseController
{
    protected $managerService;

    /**
     * khởi tạo controller với service
     */
    public function __construct(ManagerService $managerService)
    {
        $this->managerService = $managerService;
    }

    /**
     * xem chi tiết thông tin của manager đang đăng nhập
     */
    public function getProfile(): JsonResponse
    {
        $managerId = Auth::id();
        
        return $this->executeService(
            fn () => $this->managerService->getManagerDetail($managerId),
            'Lấy thông tin chi tiết cá nhân thành công'
        );
    }

    /**
     * cập nhật thông tin chi tiết của manager đang đăng nhập
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $managerId = Auth::id();
        
        return $this->executeService(
            function () use ($request, $managerId) {
                // Validate input
                $validator = Validator::make($request->all(), [
                    'full_name' => 'nullable|string|max:255',
                    'rank' => 'nullable|string|max:100',
                    'birth_year' => 'nullable|integer|min:1900|max:2010',
                    'hometown' => 'nullable|string|max:255',
                    'phone_number' => 'nullable|string|max:20',
                    'is_party_member' => 'nullable|boolean',
                    'photo_url' => 'nullable|string',
                    'management_unit' => 'nullable|string|max:255',
                    'father_name' => 'nullable|string|max:255',
                    'father_birth_year' => 'nullable|integer|min:1900|max:2000',
                    'mother_name' => 'nullable|string|max:255',
                    'mother_birth_year' => 'nullable|integer|min:1900|max:2000',
                    'father_hometown' => 'nullable|string|max:255',
                    'mother_hometown' => 'nullable|string|max:255',
                    'permanent_address' => 'nullable|string',
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                return $this->managerService->updateManagerDetail($managerId, $validator->validated());
            },
            'Cập nhật thông tin chi tiết thành công'
        );
    }
}
