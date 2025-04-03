<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // Added Request import

class StudentProfileController extends Controller
{
    /**
     * Lấy thông tin profile của student đang đăng nhập.
     * Fetches the profile information of the currently logged-in student.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse Trả về thông tin user dưới dạng JSON, sử dụng UserResource. Returns user information as JSON using UserResource.
     */
    public function getProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Trả về thông tin user sử dụng UserResource
        // Return user information using UserResource
        return response()->json([
            'status' => 'success',
            'data' => new UserResource($user)
        ]);
    }
}
