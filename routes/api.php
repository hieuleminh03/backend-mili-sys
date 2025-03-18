<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\TermController;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckAnyRole;
use App\Http\Middleware\CustomAuthenticate;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| full routes for api
|
*/

// public routes - không yêu cầu xác thực
Route::post('login', [AuthController::class, 'login']);

// protected routes - yêu cầu xác thực
Route::middleware(CustomAuthenticate::class)->group(function () {
    // route cho người dùng
    Route::get('user', [AuthController::class, 'getUser']);
    Route::post('logout', [AuthController::class, 'logout']);
    
    // route cho admin
    Route::middleware([CheckRole::class . ':' . User::ROLE_ADMIN])->group(function () {
        // đăng ký người dùng mới
        Route::post('register', [AuthController::class, 'register']);
        
        // quản lý kỳ học
        Route::prefix('terms')->group(function () {
            Route::get('/', [TermController::class, 'getAll']);
            Route::post('/', [TermController::class, 'create']);
            Route::get('/{id}', [TermController::class, 'get']);
            Route::put('/{id}', [TermController::class, 'update']);
            Route::delete('/{id}', [TermController::class, 'delete']);
        });
        
        // quản lý lớp học
        Route::prefix('courses')->group(function () {
            Route::get('/', [CourseController::class, 'getAll']);
            Route::post('/', [CourseController::class, 'create']);
            Route::get('/getAllByTerm', [CourseController::class, 'getAllByTerm']);
            Route::get('/{id}', [CourseController::class, 'get']);
            Route::put('/{id}', [CourseController::class, 'update']);
            Route::delete('/{id}', [CourseController::class, 'delete']);
            
            // quản lý sinh viên
            Route::get('/{id}/students', [CourseController::class, 'getStudents']);
            Route::post('/{id}/students', [CourseController::class, 'enrollStudent']);
            Route::delete('/{courseId}/students/{userId}', [CourseController::class, 'unenrollStudent']); 
            Route::put('/{courseId}/students/{userId}/grade', [CourseController::class, 'updateStudentGrade']);
        });
    });
    
    // route cho sinh viên
    Route::middleware([CheckRole::class . ':' . User::ROLE_STUDENT])->prefix('student')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Bảng điều khiển sinh viên'
            ]);
        });
    });
    
    // route cho quản lý
    Route::middleware([CheckRole::class . ':' . User::ROLE_MANAGER])->prefix('manager')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Bảng điều khiển quản lý'
            ]);
        });
        
        Route::get('/students', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Danh sách sinh viên'
            ]);
        });
    });
    
    // route cho admin
    Route::middleware([CheckRole::class . ':' . User::ROLE_ADMIN])->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Bảng điều khiển admin'
            ]);
        });
        
        Route::get('/users', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Tất cả người dùng'
            ]);
        });
    });
    
    // route cho cả quản lý và admin
    Route::middleware([CheckAnyRole::class . ':' . User::ROLE_MANAGER . ',' . User::ROLE_ADMIN])->group(function () {
        Route::get('/reports', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Dữ liệu báo cáo'
            ]);
        });
    });
});

// route test authentication 
Route::get('auth-test', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'Xác thực thành công',
        'user' => auth()->check() ? auth()->user() : null
    ]);
})->middleware(CustomAuthenticate::class);