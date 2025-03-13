<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * xử lý request
     *
     * @param  \Illuminate\Http\Request  $request yêu cầu HTTP
     * @param  \Closure  $next callback tiếp theo
     * @param  string  $role vai trò cần kiểm tra
     * @return mixed kết quả xử lý
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user() || !$request->user()->hasRole($role)) {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập vào tài nguyên này',
                'errors' => ['role' => ['Bạn không có quyền truy cập vào tài nguyên này']]
            ], 403);
        }

        return $next($request);
    }
    
    /**
     * thực hiện các tác vụ cần thiết sau khi response đã được gửi đến trình duyệt
     *
     * @param  \Illuminate\Http\Request  $request yêu cầu HTTP
     * @param  \Illuminate\Http\Response  $response phản hồi HTTP
     * @return void
     */
    public function terminate($request, $response)
    {
        // không cần thiết
    }
} 