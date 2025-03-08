<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @OA\Info(
 *     title="Military Management System API",
 *     version="1.0.0",
 *     description="API Documentation for Military Management System",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 */
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
     * 
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user (Admin only)",
     *     description="Create a new user account. Only accessible to administrators.",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"student", "manager", "admin"}, example="student", description="User role (optional, defaults to student)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="The email has already been taken.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function register(AuthRegisterRequest $request)
    {
        // Log authentication info
        \Log::info('Register attempt. Auth check: ' . (auth()->check() ? 'authenticated' : 'not authenticated'));
        
        // Explicit check for authentication
        if (!auth()->check()) {
            \Log::warning('Register failed: User not authenticated');
            return response()->json([
                'message' => 'Unauthenticated.',
                'errors' => ['auth' => ['You must be logged in to register new users']]
            ], 401);
        }
        
        // Log the authenticated user
        $user = auth()->user();
        \Log::info('Authenticated user for register: ID=' . $user->id . ', Email=' . $user->email . ', Role=' . $user->role);

        try {
            $result = $this->authService->register(
                $request->validated(),
                $user
            );
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            \Log::warning('Register validation failed', ['errors' => $e->validator->errors()->toArray()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Register error: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['authorization' => [$e->getMessage()]]
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Login a user
     * 
     * @param AuthLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login a user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Authentication failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="credentials",
     *                     type="array",
     *                     @OA\Items(type="string", example="Invalid credentials")
     *                 )
     *             )
     *         )
     *     )
     * )
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
     * 
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get authenticated user information",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Token validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="array",
     *                     @OA\Items(type="string", example="Invalid token")
     *                 )
     *             )
     *         )
     *     )
     * )
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
     * 
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout the authenticated user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to logout"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="array",
     *                     @OA\Items(type="string", example="Invalid token")
     *                 )
     *             )
     *         )
     *     )
     * )
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
