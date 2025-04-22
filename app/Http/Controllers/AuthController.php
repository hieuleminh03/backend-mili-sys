<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\UserImageUpdateRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * đăng ký người dùng mới
     * chỉ admin mới được tạo mới tài khoản
     *
     * @param  AuthRegisterRequest  $request  dữ liệu đăng ký
     * @return JsonResponse kết quả đăng ký
     */
    public function register(AuthRegisterRequest $request): JsonResponse
    {
        // check xem người dùng đã đăng nhập chưa
        if (! auth()->check()) {
            return $this->errorResponse(
                'Vui lòng đăng nhập để tiếp tục',
                ['auth' => ['Bạn cần đăng nhập để thực hiện thao tác này']],
                401
            );
        }

        $user = auth()->user();

        // check quyền admin
        if ($user->role !== 'admin') {
            return $this->errorResponse(
                'Bạn không có quyền thực hiện hành động này',
                ['permission' => ['Chỉ người dùng có quyền admin mới được phép đăng ký tài khoản mới']],
                403
            );
        }

        return $this->executeService(
            fn () => $this->authService->register($request->validated(), $user),
            'Đăng ký người dùng thành công',
            201
        );
    }

    /**
     * đăng nhập người dùng
     * trả về token và một số thông tin người dùng như role, id, email, name
     *
     * @param  AuthLoginRequest  $request  dữ liệu đăng nhập
     * @return JsonResponse kết quả đăng nhập
     */
    public function login(AuthLoginRequest $request): JsonResponse
    {
        return $this->executeService(
            fn () => $this->authService->login($request->validated()),
            'Đăng nhập thành công'
        );
    }

    /**
     * lấy thông tin người dùng hiện tại
     *
     * @return JsonResponse thông tin người dùng
     */
    public function getUser(): JsonResponse
    {
        return $this->executeService(
            fn () => ['user' => $this->authService->getAuthenticatedUser()],
            'Lấy thông tin người dùng thành công'
        );
    }

    /**
     * đăng xuất người dùng
     *
     * @return JsonResponse kết quả đăng xuất
     */
    public function logout(): JsonResponse
    {
        return $this->executeService(
            fn () => $this->authService->logout(),
            'Đăng xuất thành công'
        );
    }

    /**
     * cập nhật ảnh đại diện cho người dùng
     * chỉ admin hoặc chính người dùng đó mới có quyền cập nhật
     *
     * @param UserImageUpdateRequest $request yêu cầu cập nhật ảnh
     * @param int|null $userId ID của người dùng cần cập nhật
     * @return JsonResponse kết quả cập nhật
     */
    public function updateUserImage(UserImageUpdateRequest $request, ?int $userId = null): JsonResponse
    {
        // If userId is null, use the authenticated user's ID
        $userId = $userId ?? auth()->id();
        
        return $this->executeService(
            fn () => $this->authService->updateUserImage($userId, $request->image),
            'Cập nhật ảnh đại diện thành công'
        );
    }
}
