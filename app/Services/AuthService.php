<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
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
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(array $userData)
    {
        $validator = Validator::make($userData, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
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
} 