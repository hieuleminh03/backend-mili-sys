<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        
        // Convert exceptions to JSON responses for API requests
        $this->renderable(function (\Exception $e, $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                $status = 400;
                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                    $response['message'] = 'Unauthenticated';
                }
                
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    $status = 403;
                    $response['message'] = 'This action is unauthorized';
                }

                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $status = 404;
                    $response['message'] = 'Resource not found';
                }
                
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $status = 404;
                    $response['message'] = 'Not found';
                }

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $status = 422;
                    $response['message'] = 'Validation error';
                    $response['errors'] = $e->errors();
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    $status = 405;
                    $response['message'] = 'Method not allowed';
                }
                
                // JWT specific exceptions
                if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                    $status = 401;
                    $response['message'] = 'Token validation failed';
                }
                
                if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                    $status = 401;
                    $response['message'] = 'Token has expired';
                }
                
                if ($e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
                    $status = 401;
                    $response['message'] = 'Token not provided or could not be parsed';
                }

                return response()->json($response, $status);
            }
            
            return null; // Let Laravel handle non-API exceptions
        });
    }
} 