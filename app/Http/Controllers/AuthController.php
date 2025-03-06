<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * AuthController constructor.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user
     *
     * @param AuthRegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(AuthRegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 400);
        }
    }

    /**
     * Login a user
     * 
     * @param AuthLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(AuthLoginRequest $request)
    {
        try {
            $token = $this->authService->login($request->validated());
            return response()->json(compact('token'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'errors' => ['credentials' => [$e->getMessage()]]
            ], $e->getCode() ?: 401);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Server error',
                'errors' => ['token' => ['Could not create token']]
            ], 500);
        }
    }

    /**
     * Get User Info
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser()
    {
        try {
            $user = $this->authService->getAuthenticatedUser();
            return response()->json(compact('user'));
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token validation failed',
                'errors' => ['token' => ['Invalid token']]
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['user' => [$e->getMessage()]]
            ], $e->getCode() ?: 404);
        }
    }

    /**
     * Logout a user
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            $this->authService->logout();
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Failed to logout',
                'errors' => ['token' => ['Invalid token']]
            ], 400);
        }
    }
}
