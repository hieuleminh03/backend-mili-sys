<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * danh sách các exception và level log tương ứng
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * danh sách các exception không apply log
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * danh sách các input không flash vào session trong trường hợp validation exception
     * phần này đảm bảo privacy cho các input này
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * cài các callback để process cho các exception
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        
        // convert exception thành response json cho request api
        $this->renderable(function (\Exception $e, $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                $status = 400;
                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];

                // Authentication exceptions
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                    $response['message'] = 'Unauthenticated';
                }
                
                // Authorization exceptions
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    $status = 403;
                    $response['message'] = 'This action is not allowed';
                }

                // Resource not found
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $status = 404;
                    $response['message'] = 'Resource not found';
                }
                
                // Route not found
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $status = 404;
                    $response['message'] = 'Not found';
                }

                // Validation errors
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $status = 422;
                    $response['message'] = 'Validation error';
                    $response['errors'] = $e->errors();
                }

                // Method not allowed
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    $status = 405;
                    $response['message'] = 'Method not allowed';
                }
                
                // JWT specific exceptions
                if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                    $status = 401;
                    $response['message'] = 'Token is invalid';
                }
                
                if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                    $status = 401;
                    $response['message'] = 'Token has expired';
                }
                
                if ($e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
                    $status = 401;
                    $response['message'] = 'Token is not provided or could not be parsed';
                }

                return response()->json($response, $status);
            }
            
            return null; // chuyển cho laravel xử lý exception không phải API
        });
    }
} 