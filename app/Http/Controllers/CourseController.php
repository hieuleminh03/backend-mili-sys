<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseRequest;
use App\Http\Requests\EnrollmentRequest;
use App\Http\Requests\GradeRequest;
use App\Services\CourseService;
use Exception;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    /**
     * The course service instance.
     *
     * @var CourseService
     */
    protected $courseService;

    /**
     * Create a new controller instance.
     *
     * @param CourseService $courseService
     */
    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * Display a listing of the courses.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $courses = $this->courseService->getAllCourses();
        
        return response()->json([
            'status' => 'success',
            'data' => $courses
        ]);
    }

    /**
     * Store a newly created course.
     *
     * @param CourseRequest $request
     * @return JsonResponse
     */
    public function store(CourseRequest $request): JsonResponse
    {
        try {
            $course = $this->courseService->createCourse($request->validated());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Course created successfully',
                'data' => $course
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified course.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $course = $this->courseService->getCourse($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $course
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ], 404);
        }
    }

    /**
     * Update the specified course.
     *
     * @param CourseRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(CourseRequest $request, int $id): JsonResponse
    {
        try {
            $course = $this->courseService->updateCourse($id, $request->validated());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Course updated successfully',
                'data' => $course
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Course not found' ? 404 : 422);
        }
    }

    /**
     * Remove the specified course.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->courseService->deleteCourse($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Course deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Course not found' ? 404 : 422);
        }
    }

    /**
     * Get students enrolled in a course.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getStudents(int $id): JsonResponse
    {
        try {
            $students = $this->courseService->getCourseStudents($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $students
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Course not found' ? 404 : 422);
        }
    }

    /**
     * Enroll a student in a course.
     *
     * @param EnrollmentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function enrollStudent(EnrollmentRequest $request, int $id): JsonResponse
    {
        try {
            $enrollment = $this->courseService->enrollStudent($id, $request->user_id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Student enrolled successfully',
                'data' => $enrollment
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update a student's grade in a course.
     *
     * @param GradeRequest $request
     * @param int $courseId
     * @param int $userId
     * @return JsonResponse
     */
    public function updateStudentGrade(GradeRequest $request, int $courseId, int $userId): JsonResponse
    {
        try {
            $enrollment = $this->courseService->updateStudentGrade($courseId, $userId, $request->validated());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Student grade updated successfully',
                'data' => $enrollment
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
} 