<?php

namespace App\Services;

use App\Models\Course;
use App\Models\StudentCourse;
use App\Models\Term;
use App\Models\User;
use App\Http\Resources\CourseResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CourseService
{
    /**
     * Get all courses with related data.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllCourses(): AnonymousResourceCollection
    {
        $courses = Course::with(['term'])->get();
        return CourseResource::collection($courses);
    }

    /**
     * Get a specific course with its relations.
     *
     * @param int $id
     * @return CourseResource
     * @throws \Exception
     */
    public function getCourse(int $id): CourseResource
    {
        try {
            $course = Course::with(['term'])->find($id);
            if (!$course) {
                throw new \Exception('Không tìm thấy lớp học', 422);
            }
            return new CourseResource($course);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new course.
     *
     * @param array $data
     * @return CourseResource
     * @throws \Exception
     */
    public function createCourse(array $data): CourseResource
    {
        // Kiểm tra term_id tồn tại trước khi bắt đầu transaction
        $termId = $data['term_id'] ?? null;
        try {
            // Kiểm tra và throw exception rõ ràng nếu term không tồn tại
            if (!Term::where('id', $termId)->exists()) {
                \Log::error('Term not found: ' . $termId);
                throw new \Exception('Kỳ học không tồn tại trong hệ thống');
            }
            
            $course = DB::transaction(function () use ($data) {
                // Tự động tạo mã lớp học nếu không được cung cấp
                if (!isset($data['code'])) {
                    $data['code'] = Course::generateCode();
                }
                
                // Tạo và trả về lớp học với các quan hệ
                $course = Course::create($data);
                return $course->fresh(['term']);
            });
            
            return new CourseResource($course);
        } catch (\Exception $e) {
            \Log::error('Error creating course: ' . $e->getMessage());
            // Chuyển tiếp exception để Handler xử lý
            if (strpos($e->getMessage(), 'Kỳ học không tồn tại') !== false) {
                throw new \Exception('Kỳ học không tồn tại trong hệ thống', 422);
            }
            throw $e;
        }
    }

    /**
     * cập nhật thông tin lớp học
     *
     * @param int $id
     * @param array $data
     * @return CourseResource
     * @throws \Exception
     */
    public function updateCourse(int $id, array $data): CourseResource
    {
        try {
            $course = DB::transaction(function () use ($id, $data) {
                $course = Course::find($id);
                if (!$course) {
                    throw new \Exception('Không tìm thấy lớp học', 422);
                }
                
                // Không cho phép cập nhật term_id
                if (isset($data['term_id'])) {
                    throw new \Exception('Không thể thay đổi kỳ học sau khi đã tạo lớp', 422);
                }
                
                // Nếu cập nhật giới hạn đăng ký, kiểm tra không nhỏ hơn số SV hiện tại
                if (isset($data['enroll_limit'])) {
                    $currentStudentCount = $course->getCurrentStudentCount();
                    if ($data['enroll_limit'] < $currentStudentCount) {
                        throw new \Exception(
                            "Giới hạn đăng ký không thể nhỏ hơn số sinh viên đã đăng ký hiện tại ($currentStudentCount)",
                            422
                        );
                    }
                }
                
                $course->update($data);
                return $course->fresh(['term']);
            });
            
            return new CourseResource($course);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * xóa một lớp học nếu không có sinh viên đăng ký
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteCourse(int $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $course = Course::find($id);
                if (!$course) {
                    throw new \Exception('Không tìm thấy lớp học', 422);
                }
                
                // Kiểm tra xem có sinh viên nào đăng ký không
                if ($course->students()->count() > 0) {
                    throw new \Exception('Không thể xóa lớp học vì có sinh viên đã đăng ký');
                }
                
                return $course->delete();
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * lấy danh sách sinh viên đăng ký trong một lớp học với điểm số
     *
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function getCourseStudents(int $id): array
    {
        try {
            $course = Course::find($id);
            if (!$course) {
                throw new \Exception('Không tìm thấy lớp học', 422);
            }
            return $course->getStudentsWithGrades()->toArray();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * đăng ký sinh viên vào lớp học
     *
     * @param int $courseId
     * @param int $userId
     * @return StudentCourse
     * @throws \Exception
     */
    public function enrollStudent(int $courseId, int $userId): StudentCourse
    {
        try {
            return DB::transaction(function () use ($courseId, $userId) {
                $course = Course::with('term')->find($courseId);
                if (!$course) {
                    throw new \Exception('Không tìm thấy lớp học', 422);
                }
                
                // Kiểm tra xem thời gian đăng ký còn mở không
                if (now()->gt($course->term->roster_deadline)) {
                    throw new \Exception('Thời gian đăng ký đã kết thúc cho kỳ học này');
                }
                
                // Kiểm tra xem lớp còn chỗ không
                if (!$course->hasAvailableSlots()) {
                    throw new \Exception('Lớp học đã đạt giới hạn đăng ký tối đa');
                }
                
                // Kiểm tra xem người dùng có phải là sinh viên không
                $student = User::find($userId);
                if (!$student) {
                    throw new \Exception('Không tìm thấy sinh viên', 422);
                }
                
                if (!$student->isStudent()) {
                    throw new \Exception('Người dùng được chọn không phải là sinh viên');
                }
                
                // Kiểm tra đăng ký đã tồn tại
                $existingEnrollment = StudentCourse::where('course_id', $courseId)
                    ->where('user_id', $userId)
                    ->first();
                    
                if ($existingEnrollment) {
                    throw new \Exception('Sinh viên đã đăng ký lớp học này');
                }
                
                // Tạo và trả về đăng ký
                $enrollment = StudentCourse::create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'status' => 'enrolled',
                ]);
                
                return $enrollment;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * cập nhật điểm của sinh viên trong một lớp học
     *
     * @param int $courseId
     * @param int $userId
     * @param array $data
     * @return StudentCourse
     * @throws \Exception
     */
    public function updateStudentGrade(int $courseId, int $userId, array $data): StudentCourse
    {
        try {
            return DB::transaction(function () use ($courseId, $userId, $data) {
                $course = Course::with('term')->find($courseId);
                if (!$course) {
                    throw new \Exception('Không tìm thấy lớp học', 422);
                }
                
                // Kiểm tra xem thời gian nhập điểm đã bắt đầu chưa
                if (now()->lt($course->term->grade_entry_date)) {
                    throw new \Exception('Thời gian nhập điểm chưa bắt đầu cho kỳ học này');
                }
                
                // Tìm đăng ký
                $enrollment = StudentCourse::where('course_id', $courseId)
                    ->where('user_id', $userId)
                    ->first();
                
                if (!$enrollment) {
                    throw new \Exception('Không tìm thấy đăng ký học phần', 422);
                }
                
                // Loại bỏ status nếu có trong request, tránh lỗi mass assignment
                unset($data['status']);
                
                // Cập nhật điểm
                $enrollment->update($data);
                
                // Tính toán điểm tổng kết nếu có cả điểm giữa kỳ và cuối kỳ
                $totalGradeUpdated = false;
                
                if (isset($data['midterm_grade']) || isset($data['final_grade'])) {
                    // Nếu có đủ cả điểm giữa kỳ và cuối kỳ, tính điểm tổng kết
                    if (isset($enrollment->midterm_grade) && isset($enrollment->final_grade)) {
                        $enrollment->total_grade = $course->calculateTotalGrade(
                            $enrollment->midterm_grade,
                            $enrollment->final_grade
                        );
                        $totalGradeUpdated = true;
                        
                        // Tự động cập nhật status dựa trên điểm tổng kết
                        if ($enrollment->total_grade < 4) {
                            $enrollment->status = 'failed';
                        } else if ($enrollment->status !== 'dropped') {
                            // Nếu không phải dropped thì chuyển thành completed
                            $enrollment->status = 'completed';
                        }
                        
                        $enrollment->save();
                    }
                }
                
                return $enrollment;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * hủy đăng ký sinh viên khỏi lớp học
     *
     * @param int $courseId
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function unenrollStudent(int $courseId, int $userId): bool
    {
        try {
            return DB::transaction(function () use ($courseId, $userId) {
                // Kiểm tra lớp học tồn tại
                $course = Course::with('term')->find($courseId);
                if (!$course) {
                    throw new \Exception('Không tìm thấy lớp học', 422);
                }
                
                // Kiểm tra xem thời gian đăng ký còn mở không
                if (now()->gt($course->term->roster_deadline)) {
                    throw new \Exception('Thời gian đăng ký đã kết thúc cho kỳ học này');
                }
                
                // Tìm đăng ký - kiểm tra bằng tay thay vì dùng firstOrFail để tránh lỗi 404
                $enrollment = StudentCourse::where('course_id', $courseId)
                    ->where('user_id', $userId)
                    ->first();
                
                if (!$enrollment) {
                    throw new \Exception('Không tìm thấy đăng ký học phần');
                }
                
                // Xóa đăng ký
                $result = $enrollment->delete();
                
                return $result;
            });
        } catch (\Exception $e) {
            // Xử lý exception cụ thể
            if (strpos($e->getMessage(), 'Không tìm thấy') !== false) {
                throw new \Exception($e->getMessage(), 422);
            }
            throw $e;
        }
    }

    /**
     * lấy danh sách các lớp học theo kỳ học
     *
     * @param int $termId mã kỳ học
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Exception
     */
    public function getCoursesByTerm(int $termId): AnonymousResourceCollection
    {
        try {
            // Kiểm tra term tồn tại
            $term = Term::find($termId);
            if (!$term) {
                throw new \Exception('Không tìm thấy kỳ học', 422);
            }
            
            $courses = Course::with(['term'])
                ->where('term_id', $termId)
                ->get();
                
            return CourseResource::collection($courses);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * đăng ký nhiều sinh viên vào lớp học
     *
     * @param int $courseId mã lớp học
     * @param array $studentIds danh sách mã sinh viên
     * @return array kết quả đăng ký
     * @throws \Exception
     */
    public function bulkEnrollStudents(int $courseId, array $studentIds): array
    {
        try {
            return DB::transaction(function () use ($courseId, $studentIds) {
                $course = Course::with('term')->find($courseId);
                if (!$course) {
                    throw new \Exception('Không tìm thấy lớp học', 422);
                }
                
                // Kiểm tra xem thời gian đăng ký còn mở không
                if (now()->gt($course->term->roster_deadline)) {
                    throw new \Exception('Thời gian đăng ký đã kết thúc cho kỳ học này');
                }
                
                // Kiểm tra xem lớp còn đủ chỗ trống không
                $currentStudentCount = $course->getCurrentStudentCount();
                $requestedStudentCount = count($studentIds);
                
                if ($currentStudentCount + $requestedStudentCount > $course->enroll_limit) {
                    throw new \Exception(
                        sprintf(
                            'Không thể đăng ký %d sinh viên. Lớp học chỉ còn %d chỗ trống (giới hạn %d, hiện tại %d)',
                            $requestedStudentCount,
                            $course->enroll_limit - $currentStudentCount,
                            $course->enroll_limit,
                            $currentStudentCount
                        )
                    );
                }
                
                // Lấy danh sách sinh viên hiện có trong lớp
                $existingStudentIds = StudentCourse::where('course_id', $courseId)
                    ->pluck('user_id')
                    ->toArray();
                
                $result = [
                    'success' => [],
                    'failed' => [],
                    'already_enrolled' => []
                ];
                
                // Xử lý từng sinh viên
                foreach ($studentIds as $studentId) {
                    // Kiểm tra xem sinh viên đã đăng ký chưa
                    if (in_array($studentId, $existingStudentIds)) {
                        $result['already_enrolled'][] = $studentId;
                        continue;
                    }
                    
                    // Kiểm tra xem có phải sinh viên không
                    $student = User::find($studentId);
                    if (!$student || !$student->isStudent()) {
                        $result['failed'][] = $studentId;
                        continue;
                    }
                    
                    // Đăng ký sinh viên
                    StudentCourse::create([
                        'user_id' => $studentId,
                        'course_id' => $courseId,
                        'status' => 'enrolled',
                    ]);
                    
                    $result['success'][] = $studentId;
                }
                
                // Tạo thông báo tổng hợp
                $successCount = count($result['success']);
                $failedCount = count($result['failed']);
                $alreadyEnrolledCount = count($result['already_enrolled']);
                
                $message = "";
                if ($successCount > 0) {
                    $message .= "Đã đăng ký thành công $successCount sinh viên. ";
                }
                if ($failedCount > 0) {
                    $message .= "Không thể đăng ký $failedCount sinh viên. ";
                }
                if ($alreadyEnrolledCount > 0) {
                    $message .= "Có $alreadyEnrolledCount sinh viên đã được đăng ký trước đó. ";
                }
                
                $result['message'] = trim($message);
                
                return $result;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * cập nhật điểm hàng loạt cho sinh viên trong một lớp học
     *
     * @param int $courseId mã lớp học
     * @param array $grades danh sách điểm cần cập nhật
     * @return array kết quả cập nhật
     * @throws \Exception
     */
    public function bulkUpdateGrades(int $courseId, array $grades): array
    {
        try {
            return DB::transaction(function () use ($courseId, $grades) {
                // Kiểm tra lớp học tồn tại
                $course = Course::with('term')->find($courseId);
                if (!$course) {
                    throw new \Exception('Không tìm thấy lớp học', 422);
                }
                
                // Kiểm tra xem thời gian nhập điểm đã bắt đầu chưa
                if (now()->lt($course->term->grade_entry_date)) {
                    throw new \Exception('Thời gian nhập điểm chưa bắt đầu cho kỳ học này');
                }
                
                // Lấy danh sách đăng ký học phần hiện có trong lớp (tối ưu truy vấn)
                $existingEnrollments = StudentCourse::where('course_id', $courseId)
                    ->get()
                    ->keyBy('user_id');
                
                $result = [
                    'success' => [],
                    'failed' => []
                ];
                
                // Xử lý từng mục trong danh sách điểm
                foreach ($grades as $gradeData) {
                    $userId = $gradeData['user_id'];
                    
                    // Kiểm tra xem học viên có trong lớp không
                    if (!$existingEnrollments->has($userId)) {
                        $result['failed'][] = $userId;
                        continue;
                    }
                    
                    $enrollment = $existingEnrollments[$userId];
                    
                    // Chuẩn bị dữ liệu cập nhật
                    $updateData = [];
                    
                    if (isset($gradeData['midterm_grade'])) {
                        $updateData['midterm_grade'] = $gradeData['midterm_grade'];
                    }
                    
                    if (isset($gradeData['final_grade'])) {
                        $updateData['final_grade'] = $gradeData['final_grade'];
                    }
                    
                    // Cập nhật điểm
                    try {
                        $enrollment->update($updateData);
                        
                        // Tính toán điểm tổng kết nếu có đủ thông tin
                        if (
                            ($enrollment->midterm_grade !== null || isset($updateData['midterm_grade'])) && 
                            ($enrollment->final_grade !== null || isset($updateData['final_grade']))
                        ) {
                            // Lấy điểm cuối cùng (sau khi cập nhật)
                            $midtermGrade = isset($updateData['midterm_grade']) 
                                ? $updateData['midterm_grade'] 
                                : $enrollment->midterm_grade;
                                
                            $finalGrade = isset($updateData['final_grade']) 
                                ? $updateData['final_grade'] 
                                : $enrollment->final_grade;
                            
                            // Tính toán điểm tổng
                            $totalGrade = $course->calculateTotalGrade($midtermGrade, $finalGrade);
                            $enrollment->total_grade = $totalGrade;
                            
                            // Cập nhật trạng thái dựa vào điểm
                            if ($totalGrade < $course->pass_grade) {
                                $enrollment->status = 'failed';
                            } else if ($enrollment->status !== 'dropped') {
                                $enrollment->status = 'completed';
                            }
                            
                            $enrollment->save();
                        }
                        
                        $result['success'][] = $userId;
                    } catch (\Exception $e) {
                        $result['failed'][] = $userId;
                    }
                }
                
                // Tạo thông báo tổng hợp
                $successCount = count($result['success']);
                $failedCount = count($result['failed']);
                
                $message = "";
                if ($successCount > 0) {
                    $message .= "Đã cập nhật điểm thành công cho $successCount học viên. ";
                }
                if ($failedCount > 0) {
                    $message .= "Không thể cập nhật điểm cho $failedCount học viên. ";
                }
                
                $result['message'] = trim($message);
                
                return $result;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
} 