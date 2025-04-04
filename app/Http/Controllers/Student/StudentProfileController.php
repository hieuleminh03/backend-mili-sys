<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseController;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

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
                $user = auth()->user();

                return new UserResource($user);
            },
            'Lấy thông tin profile thành công'
        );
    }
}
