<?php

use App\Http\Controllers\Admin\AllowanceController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\FitnessTestController;
use App\Http\Controllers\Admin\ManagerController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\TermController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Manager\FitnessAssessmentController;
use App\Http\Controllers\Manager\ManagerClassController;
use App\Http\Controllers\Manager\SearchController as ManagerSearchController;
use App\Http\Controllers\Manager\ViolationController;
use App\Http\Controllers\Student\StudentAllowanceController;
use App\Http\Controllers\Student\StudentClassController;
use App\Http\Controllers\Student\StudentCourseController;
use App\Http\Controllers\Student\StudentEquipmentController;
use App\Http\Controllers\Student\StudentFitnessAssessmentController;
use App\Http\Controllers\Student\StudentGradeController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\Student\StudentViolationController;
use App\Http\Middleware\CheckAnyRole;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CustomAuthenticate;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Military Management System API Routes
|
*/

/**
 * Public routes - không yêu cầu xác thực
 */
Route::post('login', [AuthController::class, 'login']);
Route::get('auth-test', function () {
    return response()->json([
        'status'  => 'success',
        'message' => 'Xác thực thành công',
        'user'    => auth()->check() ? auth()->user() : null,
    ]);
})->middleware(CustomAuthenticate::class);

/**
 * Protected routes - yêu cầu xác thực
 */
Route::middleware(CustomAuthenticate::class)->group(function () {
    /**
     * Common routes - các route chung cho mọi người dùng đã xác thực
     */
    Route::prefix('auth')->group(function () {
        Route::get('/user', [AuthController::class, 'getUser']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    /**
     * Shared routes - các route dùng chung cho nhiều vai trò
     */
    Route::middleware([CheckAnyRole::class . ':' . User::ROLE_MANAGER . ',' . User::ROLE_ADMIN])->group(function () {
        Route::get('/reports', function () {
            return response()->json([
                'status'  => 'success',
                'message' => 'Dữ liệu báo cáo',
            ]);
        });
    });

    /**
     * Student routes - các route dành cho học viên
     */
    Route::middleware([CheckRole::class . ':' . User::ROLE_STUDENT])
        ->prefix('student')
        ->group(function () {
            // Trang chủ học viên
            Route::get('/dashboard', function () {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Bảng điều khiển sinh viên',
                ]);
            });

            // Thông tin cá nhân
            Route::get('/profile', [StudentProfileController::class, 'getProfile']);

            // Quản lý lớp học
            Route::prefix('class')->group(function () {
                Route::get('/', [StudentClassController::class, 'getMyClass']);
                Route::get('/classmates', [StudentClassController::class, 'getClassmates']);
            });

            // Quản lý khóa học
            Route::prefix('courses')->group(function () {
                Route::get('/', [StudentCourseController::class, 'getMyCourses']);
                Route::get('/grades', [StudentGradeController::class, 'getMyGrades']);
            });

            // Quản lý thể lực
            Route::get('/fitness-assessments', [StudentFitnessAssessmentController::class, 'getMyAssessments']);

            // Quản lý vi phạm
            Route::get('/violations', [StudentViolationController::class, 'getMyViolations']);

            // Quản lý quân tư trang
            Route::prefix('equipment')->group(function () {
                Route::get('/', [StudentEquipmentController::class, 'getMyEquipment']);
                Route::put('/{receiptId}', [StudentEquipmentController::class, 'updateReceiptStatus']);
            });

            // Quản lý phụ cấp
            Route::prefix('allowances')->group(function () {
                Route::get('/', [StudentAllowanceController::class, 'getMyAllowances']);
                Route::put('/{allowanceId}', [StudentAllowanceController::class, 'updateAllowanceStatus']);
            });
        });

    /**
     * Manager routes - các route dành cho quản lý
     */
    Route::middleware([CheckRole::class . ':' . User::ROLE_MANAGER])
        ->prefix('manager')
        ->group(function () {
            // Trang chủ quản lý
            Route::get('/dashboard', function () {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Bảng điều khiển quản lý',
                ]);
            });

            // Quản lý học viên
            Route::prefix('students')->group(function () {
                Route::get('/', function () {
                    return response()->json([
                        'status'  => 'success',
                        'message' => 'Danh sách sinh viên',
                    ]);
                });
                Route::post('/search', [ManagerSearchController::class, 'searchStudents']);
            });

            // Quản lý lớp học
            Route::prefix('class')->group(function () {
                Route::get('/', [ManagerClassController::class, 'getMyClass']);
                Route::get('/students/{studentId}', [ManagerClassController::class, 'getStudentDetail']);
                Route::put('/students/{studentId}', [ManagerClassController::class, 'updateStudent']);
                Route::put('/students/{studentId}/assign-monitor', [ManagerClassController::class, 'assignMonitor']);
                Route::put('/students/{studentId}/assign-vice-monitor', [ManagerClassController::class, 'assignViceMonitor']);
                Route::put('/students/{studentId}/assign-student', [ManagerClassController::class, 'assignStudent']);
            });

            // Quản lý vi phạm
            Route::prefix('violations')->group(function () {
                Route::get('/student/{studentId}', [ViolationController::class, 'getStudentViolations']);
                Route::post('/', [ViolationController::class, 'create']);
                Route::put('/{id}', [ViolationController::class, 'update']);
                Route::delete('/{id}', [ViolationController::class, 'delete']);
            });

            // Quản lý đánh giá thể lực
            Route::prefix('fitness')->group(function () {
                Route::get('/tests', [FitnessAssessmentController::class, 'getAllFitnessTests']);
                Route::get('/sessions', [FitnessAssessmentController::class, 'getAllSessions']);
                Route::get('/current-session', [FitnessAssessmentController::class, 'getCurrentWeekSession']);
                Route::get('/sessions/{sessionId}/assessments', [FitnessAssessmentController::class, 'getSessionAssessments']);
                Route::post('/assessments', [FitnessAssessmentController::class, 'recordAssessment']);
                Route::post('/assessments/batch', [FitnessAssessmentController::class, 'batchRecordAssessments']);
            });
        });

    /**
     * Admin routes - các route dành cho admin
     */
    Route::middleware([CheckRole::class . ':' . User::ROLE_ADMIN])
        ->group(function () {
            // Quản lý người dùng
            Route::post('/register', [AuthController::class, 'register']);

            Route::prefix('admin')->group(function () {
                // Trang chủ admin
                Route::get('/dashboard', function () {
                    return response()->json([
                        'status'  => 'success',
                        'message' => 'Bảng điều khiển admin',
                    ]);
                });

                // Quản lý người dùng
                Route::get('/users', function () {
                    return response()->json([
                        'status'  => 'success',
                        'message' => 'Tất cả người dùng',
                    ]);
                });

                // Tìm kiếm
                Route::post('/search/student', [SearchController::class, 'searchStudents']);

                // Quản lý quản lý viên
                Route::prefix('managers')->group(function () {
                    Route::get('/', [ManagerController::class, 'getAllManagers']);
                    Route::get('/{id}', [ManagerController::class, 'getManagerDetail']);
                    Route::put('/{id}', [ManagerController::class, 'updateManagerDetail']);
                });

                // Quản lý học kỳ
                Route::prefix('terms')->group(function () {
                    Route::get('/', [TermController::class, 'getAll']);
                    Route::post('/', [TermController::class, 'create']);
                    Route::get('/{id}', [TermController::class, 'get']);
                    Route::put('/{id}', [TermController::class, 'update']);
                    Route::delete('/{id}', [TermController::class, 'delete']);
                });

                // Quản lý khóa học
                Route::prefix('courses')->group(function () {
                    Route::get('/', [CourseController::class, 'getAll']);
                    Route::post('/', [CourseController::class, 'create']);
                    Route::get('/getAllByTerm/{id}', [CourseController::class, 'getAllByTerm']);
                    Route::get('/{id}', [CourseController::class, 'get']);
                    Route::put('/{id}', [CourseController::class, 'update']);
                    Route::delete('/{id}', [CourseController::class, 'delete']);

                    // Quản lý học viên trong khóa học
                    Route::get('/{id}/students', [CourseController::class, 'getStudents']);
                    Route::post('/{id}/students', [CourseController::class, 'enrollStudent']);
                    Route::post('/{id}/students/bulk', [CourseController::class, 'bulkEnrollStudents']);
                    Route::delete('/{courseId}/students/{userId}', [CourseController::class, 'unenrollStudent']);
                    Route::put('/{courseId}/students/{userId}/grade', [CourseController::class, 'updateStudentGrade']);
                    Route::put('/{id}/grades/bulk', [CourseController::class, 'bulkUpdateGrades']);
                });

                // Quản lý lớp học
                Route::prefix('classes')->group(function () {
                    Route::get('/', [ClassController::class, 'getAllClasses']);
                    Route::post('/', [ClassController::class, 'createClass']);
                    Route::get('/{id}', [ClassController::class, 'getClass']);
                    Route::put('/{id}', [ClassController::class, 'updateClass']);
                    Route::delete('/{id}', [ClassController::class, 'deleteClass']);

                    // Quản lý học viên trong lớp
                    Route::post('/{classId}/students', [ClassController::class, 'addStudentToClass']);
                    Route::post('/{classId}/students/bulk', [ClassController::class, 'bulkAddStudentsToClass']);
                    Route::get('/{classId}/students/{studentId}', [ClassController::class, 'getStudentClassDetail']);
                    Route::put('/{classId}/students/{studentId}', [ClassController::class, 'updateStudentInClass']);
                    Route::delete('/{classId}/students/{studentId}', [ClassController::class, 'removeStudentFromClass']);

                    // Phân công lớp trưởng, lớp phó
                    Route::put('/{classId}/students/{studentId}/assign-monitor', [ClassController::class, 'assignMonitor']);
                    Route::put('/{classId}/students/{studentId}/assign-vice-monitor', [ClassController::class, 'assignViceMonitor']);
                    Route::put('/{classId}/students/{studentId}/assign-student', [ClassController::class, 'assignStudent']);
                });

                // Quản lý thể lực
                Route::prefix('fitness-tests')->group(function () {
                    Route::get('/', [FitnessTestController::class, 'getAll']);
                    Route::post('/', [FitnessTestController::class, 'create']);
                    Route::get('/{id}', [FitnessTestController::class, 'get']);
                    Route::put('/{id}', [FitnessTestController::class, 'update']);
                    Route::delete('/{id}', [FitnessTestController::class, 'delete']);
                });

                // Quản lý quân tư trang
                Route::prefix('equipment')->group(function () {
                    // Quản lý loại quân tư trang
                    Route::prefix('types')->group(function () {
                        Route::get('/', [EquipmentController::class, 'getAllEquipmentTypes']);
                        Route::post('/', [EquipmentController::class, 'createEquipmentType']);
                        Route::put('/{id}', [EquipmentController::class, 'updateEquipmentType']);
                        Route::delete('/{id}', [EquipmentController::class, 'deleteEquipmentType']);
                    });

                    // Quản lý phân phối quân tư trang
                    Route::prefix('distributions')->group(function () {
                        Route::get('/', [EquipmentController::class, 'getDistributions']);
                        Route::post('/', [EquipmentController::class, 'createDistribution']);
                        Route::put('/{id}', [EquipmentController::class, 'updateDistribution']);
                        Route::delete('/{id}', [EquipmentController::class, 'deleteDistribution']);
                    });

                    // Quản lý biên nhận
                    Route::post('/receipts', [EquipmentController::class, 'createReceipts']);
                    Route::get('/pending', [EquipmentController::class, 'getStudentsWithPendingEquipment']);
                    Route::get('/students/{studentId}', [EquipmentController::class, 'getStudentEquipment']);
                });

                // Quản lý phụ cấp
                Route::prefix('allowances')->group(function () {
                    Route::get('/', [AllowanceController::class, 'getAllowances']);
                    Route::post('/', [AllowanceController::class, 'createAllowance']);
                    Route::post('/bulk', [AllowanceController::class, 'createBulkAllowances']);
                    Route::put('/{id}', [AllowanceController::class, 'updateAllowance']);
                    Route::delete('/{id}', [AllowanceController::class, 'deleteAllowance']);
                    Route::get('/pending', [AllowanceController::class, 'getStudentsWithPendingAllowances']);
                    Route::get('/students/{studentId}', [AllowanceController::class, 'getStudentAllowances']);
                });
            });
        });
});
