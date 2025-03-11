<?php

namespace App\Services;

use App\Models\Course;
use App\Models\StudentCourse;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CourseService
{
    /**
     * Get all courses with related data.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllCourses(): Collection
    {
        return Course::with(['term', 'manager'])->get();
    }

    /**
     * Get a specific course with its relations.
     *
     * @param int $id
     * @return Course
     */
    public function getCourse(int $id): Course
    {
        return Course::with(['term', 'manager'])->findOrFail($id);
    }

    /**
     * Create a new course.
     *
     * @param array $data
     * @return Course
     * @throws \Exception
     */
    public function createCourse(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            // Validate manager is valid
            $manager = User::find($data['manager_id']);
            if (!$manager || !$manager->isManager()) {
                throw new \Exception('The selected user is not a manager');
            }
            
            // Create and return the course with its relations
            $course = Course::create($data);
            return $course->fresh(['term', 'manager']);
        });
    }

    /**
     * Update an existing course.
     *
     * @param int $id
     * @param array $data
     * @return Course
     * @throws \Exception
     */
    public function updateCourse(int $id, array $data): Course
    {
        return DB::transaction(function () use ($id, $data) {
            $course = Course::findOrFail($id);
            
            // Validate manager is valid if being updated
            if (isset($data['manager_id'])) {
                $manager = User::find($data['manager_id']);
                if (!$manager || !$manager->isManager()) {
                    throw new \Exception('The selected user is not a manager');
                }
            }
            
            $course->update($data);
            return $course->fresh(['term', 'manager']);
        });
    }

    /**
     * Delete a course if it has no enrolled students.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteCourse(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $course = Course::findOrFail($id);
            
            // Check if there are any students enrolled
            if ($course->students()->count() > 0) {
                throw new \Exception('Cannot delete course because it has enrolled students');
            }
            
            return $course->delete();
        });
    }

    /**
     * Get students enrolled in a course with their grades.
     *
     * @param int $id
     * @return array
     */
    public function getCourseStudents(int $id): array
    {
        $course = Course::findOrFail($id);
        return $course->getStudentsWithGrades()->toArray();
    }

    /**
     * Enroll a student in a course.
     *
     * @param int $courseId
     * @param int $userId
     * @return StudentCourse
     * @throws \Exception
     */
    public function enrollStudent(int $courseId, int $userId): StudentCourse
    {
        return DB::transaction(function () use ($courseId, $userId) {
            $course = Course::with('term')->findOrFail($courseId);
            
            // Check if enrollment period is still open
            if (now()->gt($course->term->roster_deadline)) {
                throw new \Exception('Enrollment period has ended for this term');
            }
            
            // Check if the user is a student
            $student = User::find($userId);
            if (!$student || !$student->isStudent()) {
                throw new \Exception('The selected user is not a student');
            }
            
            // Check for existing enrollment
            $existingEnrollment = StudentCourse::where('course_id', $courseId)
                ->where('user_id', $userId)
                ->first();
                
            if ($existingEnrollment) {
                throw new \Exception('Student is already enrolled in this course');
            }
            
            // Create and return the enrollment
            return StudentCourse::create([
                'user_id' => $userId,
                'course_id' => $courseId,
                'status' => 'enrolled',
            ]);
        });
    }

    /**
     * Update a student's grade in a course.
     *
     * @param int $courseId
     * @param int $userId
     * @param array $data
     * @return StudentCourse
     * @throws \Exception
     */
    public function updateStudentGrade(int $courseId, int $userId, array $data): StudentCourse
    {
        return DB::transaction(function () use ($courseId, $userId, $data) {
            $course = Course::with('term')->findOrFail($courseId);
            
            // Check if grade entry period is open
            if (now()->lt($course->term->grade_entry_date)) {
                throw new \Exception('Grade entry period has not started for this term');
            }
            
            // Find the enrollment
            $enrollment = StudentCourse::where('course_id', $courseId)
                ->where('user_id', $userId)
                ->firstOrFail();
            
            // Update and return the enrollment
            $enrollment->update($data);
            return $enrollment;
        });
    }
} 