<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnyRole
{
    /**
     * xử lý request
     *
     * @param  \Illuminate\Http\Request  $request  yêu cầu HTTP
     * @param  \Closure  $next  callback tiếp theo
     * @param  string  ...$roles  vai trò cần kiểm tra
     * @return mixed kết quả xử lý
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'errors' => ['auth' => ['Bạn phải đăng nhập để truy cập tài nguyên này']],
            ], 401);
        }

        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Bạn không có quyền truy cập vào tài nguyên này',
            'errors' => ['role' => ['Bạn không có quyền truy cập vào tài nguyên này']],
        ], 403);
    }

    /**
     * thực hiện các tác vụ cần thiết sau khi response đã được gửi đến trình duyệt
     *
     * @param  \Illuminate\Http\Request  $request  yêu cầu HTTP
     * @param  \Illuminate\Http\Response  $response  phản hồi HTTP
     * @return void
     */
    public function terminate($request, $response)
    {
        // không cần thiết
    }
}
