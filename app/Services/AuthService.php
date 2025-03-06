<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    /**
     * Register a new user
     *
     * @param array $userData
     * @param User|null $currentUser
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function register(array $userData, ?User $currentUser = null)
    {
        // Check if current user is admin, unless this is the first admin creation
        if (!$this->isFirstAdminCreation() && (!$currentUser || !$currentUser->isAdmin())) {
            throw new Exception('Only administrators can create new accounts', 403);
        }

        $validator = Validator::make($userData, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'sometimes|string|in:student,manager,admin',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Don't allow non-admin users to create admin accounts
        if ($userData['role'] === User::ROLE_ADMIN && $currentUser && !$currentUser->isAdmin()) {
            throw new Exception('You are not authorized to create admin accounts', 403);
        }

        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'role' => $userData['role'] ?? User::ROLE_STUDENT,
        ]);

        return [
            'id' => $user->id
        ];
    }

    /**
     * Attempt to authenticate a user
     *
     * @param array $credentials
     * @return string JWT token
     * @throws JWTException
     * @throws Exception
     */
    public function login(array $credentials)
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            throw new Exception('Invalid credentials', 401);
        }

        $user = auth()->user();
        return JWTAuth::claims(['role' => $user->role])->fromUser($user);
    }

    /**
     * Get the authenticated user
     *
     * @return User
     * @throws JWTException
     */
    public function getAuthenticatedUser()
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        return $user;
    }

    /**
     * Invalidate the current token
     *
     * @return void
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    /**
     * Check if first admin creation is allowed
     * 
     * @return bool
     */
    public function isFirstAdminCreation()
    {
        // Check if admin exists
        $adminExists = User::where('role', User::ROLE_ADMIN)->exists();
        if ($adminExists) {
            return false;
        }

        // Check if env vars are set
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        $adminName = env('ADMIN_NAME', 'Admin User');

        return !empty($adminEmail) && !empty($adminPassword);
    }
} 