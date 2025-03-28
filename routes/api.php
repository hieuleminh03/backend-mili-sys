<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ManagerController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\TermController;
use App\Http\Controllers\Manager\ManagerClassController;
use App\Http\Controllers\Manager\ViolationController;
use App\Http\Controllers\Student\StudentClassController;
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
        
        // tìm kiếm
        Route::post('/search/student', [SearchController::class, 'searchStudents']);
        
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
            Route::post('/{id}/students/bulk', [CourseController::class, 'bulkEnrollStudents']);
            Route::delete('/{courseId}/students/{userId}', [CourseController::class, 'unenrollStudent']); 
            Route::put('/{courseId}/students/{userId}/grade', [CourseController::class, 'updateStudentGrade']);
            Route::put('/{id}/grades/bulk', [CourseController::class, 'bulkUpdateGrades']);
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
        
        // Route xem thông tin lớp học
        Route::prefix('class')->group(function () {
            Route::get('/', [StudentClassController::class, 'getMyClass']);
            Route::get('/classmates', [StudentClassController::class, 'getClassmates']);
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
        
        // routes quản lý vi phạm
        Route::prefix('violations')->group(function () {
            Route::get('/student/{studentId}', [ViolationController::class, 'getStudentViolations']);
            Route::post('/', [ViolationController::class, 'create']);
            Route::put('/{id}', [ViolationController::class, 'update']);
            Route::delete('/{id}', [ViolationController::class, 'delete']);
        });
        
        // Route quản lý lớp học
        Route::prefix('class')->group(function () {
            Route::get('/', [ManagerClassController::class, 'getMyClass']);
            Route::get('/students/{studentId}', [ManagerClassController::class, 'getStudentDetail']);
            Route::put('/students/{studentId}', [ManagerClassController::class, 'updateStudent']);
            Route::put('/students/{studentId}/assign-monitor', [ManagerClassController::class, 'assignMonitor']);
            Route::put('/students/{studentId}/assign-vice-monitor', [ManagerClassController::class, 'assignViceMonitor']);
            Route::put('/students/{studentId}/assign-student', [ManagerClassController::class, 'assignStudent']);
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
        
        // routes quản lý manager
        Route::prefix('managers')->group(function () {
            Route::get('/', [ManagerController::class, 'getAllManagers']);
            Route::get('/{id}', [ManagerController::class, 'getManagerDetail']);
            Route::put('/{id}', [ManagerController::class, 'updateManagerDetail']);
        });
        
        // routes quản lý lớp học
        Route::prefix('classes')->group(function () {
            Route::get('/', [ClassController::class, 'getAllClasses']);
            Route::post('/', [ClassController::class, 'createClass']);
            Route::get('/{id}', [ClassController::class, 'getClass']);
            Route::put('/{id}', [ClassController::class, 'updateClass']);
            Route::delete('/{id}', [ClassController::class, 'deleteClass']);
            
            // Student in class management
            Route::post('/{classId}/students', [ClassController::class, 'addStudentToClass']);
            Route::get('/{classId}/students/{studentId}', [ClassController::class, 'getStudentClassDetail']);
            Route::put('/{classId}/students/{studentId}', [ClassController::class, 'updateStudentInClass']);
            Route::delete('/{classId}/students/{studentId}', [ClassController::class, 'removeStudentFromClass']);
            
            // Assign monitor and vice monitor
            Route::put('/{classId}/students/{studentId}/assign-monitor', [ClassController::class, 'assignMonitor']);
            Route::put('/{classId}/students/{studentId}/assign-vice-monitor', [ClassController::class, 'assignViceMonitor']);
            Route::put('/{classId}/students/{studentId}/assign-student', [ClassController::class, 'assignStudent']);
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