<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class CustomAuthenticate
{
    /**
     * xử lý yêu cầu đến để đảm bảo người dùng đã xác thực
     * middleware JWT cho API
     *
     * @param  \Illuminate\Http\Request  $request yêu cầu HTTP
     * @param  \Closure  $next callback tiếp theo
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Bỏ qua xác thực cho một số route công khai
        $publicRoutes = ['api/login'];
        foreach ($publicRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        try {
            // Kiểm tra nếu header Authorization tồn tại
            if (!$request->header('Authorization')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vui lòng đăng nhập để tiếp tục'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            if (empty($token)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token không hợp lệ'
                ], 401);
            }

            // Xác thực người dùng
            $user = JWTAuth::setToken($token)->authenticate();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            // Thiết lập người dùng đã xác thực trong auth guard
            auth()->login($user);
            
            return $next($request);
                
        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token đã hết hạn'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token không hợp lệ'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi xác thực: ' . $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ trong quá trình xác thực'
            ], 500);
        }
    }
} 