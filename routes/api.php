<?php

use App\Http\Controllers\DevController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthController::class, 'login']);

// Test route to check JWT authentication
Route::get('auth-test', function() {
    $user = auth()->user();
    if ($user) {
        Log::info('Auth test successful. User ID: ' . $user->id);
        return response()->json([
            'status' => 'success',
            'message' => 'Authentication working',
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);
    } else {
        Log::warning('Auth test failed. No authenticated user.');
        return response()->json([
            'status' => 'error',
            'message' => 'Authentication not working'
        ], 401);
    }
})->middleware('auth');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('user', [AuthController::class, 'getUser']);
    Route::post('logout', [AuthController::class, 'logout']);
    
    // Admin only routes
    Route::middleware(['role:' . User::ROLE_ADMIN])->group(function () {
        Route::post('register', [AuthController::class, 'register']);
    });
    
    // Student routes
    Route::middleware(['role:' . User::ROLE_STUDENT])->prefix('student')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Student dashboard']);
        });
    });
    
    // Manager routes
    Route::middleware(['role:' . User::ROLE_MANAGER])->prefix('manager')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Manager dashboard']);
        });
        
        Route::get('/students', function () {
            return response()->json(['message' => 'Students list']);
        });
    });
    
    // Admin routes
    Route::middleware(['role:' . User::ROLE_ADMIN])->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Admin dashboard']);
        });
        
        Route::get('/users', function () {
            return response()->json(['message' => 'All users']);
        });
    });
    
    // Accessible by both Manager and Admin
    Route::middleware(['role.any:' . User::ROLE_MANAGER . ',' . User::ROLE_ADMIN])->group(function () {
        Route::get('/reports', function () {
            return response()->json(['message' => 'Reports data']);
        });
    });
});