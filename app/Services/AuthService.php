<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthService
{
    /**
     * đăng ký người dùng mới
     * chỉ admin mới có thể đăng ký tài khoản mới
     *
     * @param array $userData dữ liệu người dùng
     * @param User|null $currentUser người dùng hiện tại thực hiện đăng ký
     * @return array kết quả đăng ký
     * @throws ValidationException nếu dữ liệu không hợp lệ
     * @throws AuthorizationException nếu không có quyền
     * @throws HttpException nếu có lỗi khác
     */
    public function register(array $userData, ?User $currentUser = null): array
    {
        // check quyền admin
        if (!$currentUser || !$currentUser->isAdmin()) {
            throw new AuthorizationException('Chỉ người dùng có quyền admin mới được phép đăng ký tài khoản mới');
        }
        $validator = Validator::make($userData, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'sometimes|string|in:student,manager,admin',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // sử dụng giá trị mặc định nếu không truyền role
        $role = $userData['role'] ?? User::ROLE_STUDENT;

        try {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => $role,
            ]);

            Log::info('tạo mới user: ' . $user->id . ' với role: ' . $role);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ];
        } catch (Exception $e) {
            Log::error('lỗi khi tạo mới user: ' . $e->getMessage());
            throw new HttpException(500, 'Không thể tạo người dùng: ' . $e->getMessage());
        }
    }

    /**
     * đăng nhập người dùng
     * trả về token JWT và thông tin người dùng
     *
     * @param array $credentials thông tin đăng nhập
     * @return array token JWT và thông tin người dùng
     * @throws ValidationException nếu thông tin đăng nhập không hợp lệ
     * @throws HttpException nếu thông tin đăng nhập không chính xác
     * @throws JWTException nếu có lỗi liên quan đến JWT
     */
    public function login(array $credentials): array
    {
        $validator = Validator::make($credentials, [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            throw new HttpException(401, 'Thông tin đăng nhập không hợp lệ');
        }

        $user = auth()->user();
        $token = JWTAuth::claims(['role' => $user->role])->fromUser($user);
        
        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ];
    }

    /**
     * lấy thông tin người dùng đã xác thực
     *
     * @return User thông tin người dùng
     * @throws TokenInvalidException nếu token không hợp lệ
     * @throws ModelNotFoundException nếu không tìm thấy người dùng
     */
    public function getAuthenticatedUser(): User
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        if (!$user) {
            throw new ModelNotFoundException('Không tìm thấy người dùng');
        }
        
        return $user;
    }

    /**
     * đăng xuất người dùng
     * hủy bỏ token hiện tại
     *
     * @return bool kết quả đăng xuất
     * @throws JWTException nếu có lỗi liên quan đến JWT
     */
    public function logout(): bool
    {
        try {
            // check xem có token không
            $token = JWTAuth::getToken();
            if (!$token) {
                // nếu không có token, vẫn trả về true vì người dùng đã không đăng nhập
                Log::warning('Logout: Không tìm thấy token');
                return true;
            }
            
            // hủy token
            JWTAuth::invalidate($token);
            Log::info('Logout: Token đã được hủy thành công');
            return true;
        } catch (TokenExpiredException $e) {
            // nếu token đã hết hạn, vẫn coi như đăng xuất thành công
            Log::info('Logout: Token đã hết hạn trước khi đăng xuất');
            return true;
        } catch (TokenInvalidException $e) {
            // nếu token không hợp lệ, vẫn coi như đăng xuất thành công
            Log::info('Logout: Token không hợp lệ trước khi đăng xuất');
            return true;
        } catch (JWTException $e) {
            // ghi log lỗi JWT
            Log::error('Logout: Lỗi JWT - ' . $e->getMessage());
            throw $e;
        }
    }
} 