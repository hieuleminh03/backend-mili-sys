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
        $this->renderable(function (TokenInvalidException $e, $request) {
            return response()->json([
                'message' => 'Token validation failed',
                'errors' => ['token' => ['Invalid token']]
            ], 401);
        });

        $this->renderable(function (TokenExpiredException $e, $request) {
            return response()->json([
                'message' => 'Token expired',
                'errors' => ['token' => ['Token has expired']]
            ], 401);
        });

        $this->renderable(function (JWTException $e, $request) {
            return response()->json([
                'message' => 'Token not provided',
                'errors' => ['token' => ['Token not provided or could not be parsed']]
            ], 401);
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => ['auth' => ['Authentication failed']]
            ], 401);
        });

        $this->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            $modelName = strtolower(class_basename($e->getModel()));
            return response()->json([
                'message' => 'Resource not found',
                'errors' => [$modelName => ['No ' . $modelName . ' found with the specified ID']]
            ], 404);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'message' => 'Resource not found',
                'errors' => ['route' => ['The requested resource does not exist']]
            ], 404);
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Server error',
                    'errors' => ['server' => ['An unexpected error occurred']]
                ], 500);
            }
        });
    }
} 