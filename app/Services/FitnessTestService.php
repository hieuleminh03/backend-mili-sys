<?php

namespace App\Services;

use App\Http\Resources\FitnessTestCollection;
use App\Http\Resources\FitnessTestResource;
use App\Models\FitnessAssessmentSession;
use App\Models\FitnessTest;
use App\Models\StudentFitnessRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FitnessTestService
{
    /**
     * Get all fitness tests with pagination.
     *
     * @param  int  $perPage  số lượng item trên một trang
     * @param  int  $page  trang hiện tại
     * @return LengthAwarePaginator
     */
    /**
     * Lấy danh sách tất cả bài kiểm tra thể lực có phân trang
     *
     * @param  int  $perPage  số lượng item trên một trang
     * @param  int  $page  trang hiện tại
     */
    public function getAllFitnessTests(int $perPage = 15, int $page = 1): \App\Http\Resources\FitnessTestCollection
    {
        $fitnessTests = FitnessTest::with('thresholds')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        // Pass the paginator instance directly to the collection resource
        return new FitnessTestCollection($fitnessTests);
    }

    /**
     * Get a specific fitness test by ID.
     *
     * @param  int  $id  mã bài kiểm tra thể lực
     * @return FitnessTest|null
     *
     * @throws \Exception nếu không tìm thấy
     */
    /**
     * Lấy thông tin một bài kiểm tra thể lực
     *
     * @param  int  $id  mã bài kiểm tra
     *
     * @throws \Exception nếu không tìm thấy
     */
    public function getFitnessTest(int $id): \App\Http\Resources\FitnessTestResource
    {
        $fitnessTest = FitnessTest::with('thresholds')->find($id);

        if (! $fitnessTest) {
            throw new \Exception('Không tìm thấy bài kiểm tra thể lực', 404);
        }

        return new FitnessTestResource($fitnessTest);
    }

    /**
     * Create a new fitness test with thresholds.
     *
     * @param  array  $data  dữ liệu bài kiểm tra và ngưỡng đánh giá
     * @return FitnessTest
     *
     * @throws \Exception nếu xảy ra lỗi
     */
    /**
     * Tạo bài kiểm tra thể lực mới
     *
     * @param  array  $data  dữ liệu bài kiểm tra
     *
     * @throws \Exception nếu có lỗi
     */
    public function createFitnessTest(array $data): \App\Http\Resources\FitnessTestResource
    {
        try {
            return DB::transaction(function () use ($data) {
                // Tạo bài kiểm tra thể lực mới
                $fitnessTest = FitnessTest::create([
                    'name' => $data['name'],
                    'unit' => $data['unit'],
                    'higher_is_better' => $data['higher_is_better'] ?? false,
                ]);

                // Tạo thresholds cho bài kiểm tra
                $fitnessTest->thresholds()->create([
                    'excellent_threshold' => $data['excellent_threshold'],
                    'good_threshold' => $data['good_threshold'],
                    'pass_threshold' => $data['pass_threshold'],
                ]);

                $fitnessTest->load('thresholds');

                return new FitnessTestResource($fitnessTest);
            });
        } catch (\Exception $e) {
            \Log::error('Lỗi khi tạo bài kiểm tra thể lực: '.$e->getMessage());
            throw new \Exception('Không thể tạo bài kiểm tra thể lực: '.$e->getMessage(), 500);
        }
    }

    /**
     * Update an existing fitness test and its thresholds.
     *
     * @param  int  $id  mã bài kiểm tra
     * @param  array  $data  dữ liệu cần cập nhật
     * @return FitnessTest
     *
     * @throws \Exception nếu xảy ra lỗi
     */
    /**
     * Cập nhật bài kiểm tra thể lực
     *
     * @param  int  $id  mã bài kiểm tra
     * @param  array  $data  dữ liệu cập nhật
     *
     * @throws \Exception nếu có lỗi
     */
    public function updateFitnessTest(int $id, array $data): \App\Http\Resources\FitnessTestResource
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                // Fetch the actual model, not the resource
                $fitnessTest = FitnessTest::with('thresholds')->findOrFail($id);

                // Cập nhật thông tin bài kiểm tra
                if (isset($data['name'])) {
                    $fitnessTest->name = $data['name'];
                }

                if (isset($data['unit'])) {
                    $fitnessTest->unit = $data['unit'];
                }

                if (isset($data['higher_is_better'])) {
                    $fitnessTest->higher_is_better = $data['higher_is_better'];
                }

                $fitnessTest->save();

                // Cập nhật thresholds
                if ($fitnessTest->thresholds) {
                    if (isset($data['excellent_threshold'])) {
                        $fitnessTest->thresholds->excellent_threshold = $data['excellent_threshold'];
                    }

                    if (isset($data['good_threshold'])) {
                        $fitnessTest->thresholds->good_threshold = $data['good_threshold'];
                    }

                    if (isset($data['pass_threshold'])) {
                        $fitnessTest->thresholds->pass_threshold = $data['pass_threshold'];
                    }

                    $fitnessTest->thresholds->save();

                    // Kiểm tra thứ tự các ngưỡng đánh giá
                    $validationResult = $fitnessTest->thresholds->validateThresholdOrder();
                    if ($validationResult !== true) {
                        throw new \Exception('Lỗi ngưỡng đánh giá: '.implode(', ', $validationResult), 422);
                    }
                } else {
                    // Tạo mới thresholds nếu chưa có
                    $fitnessTest->thresholds()->create([
                        'excellent_threshold' => $data['excellent_threshold'] ?? 0,
                        'good_threshold' => $data['good_threshold'] ?? 0,
                        'pass_threshold' => $data['pass_threshold'] ?? 0,
                    ]);
                }

                $fitnessTest->load('thresholds');

                return new FitnessTestResource($fitnessTest);
            });
        } catch (\Exception $e) {
            \Log::error('Lỗi khi cập nhật bài kiểm tra thể lực: '.$e->getMessage());
            throw $e; // Throw lại exception để controller xử lý
        }
    }

    /**
     * Delete a fitness test.
     *
     * @param  int  $id  mã bài kiểm tra
     *
     * @throws \Exception nếu xảy ra lỗi
     */
    public function deleteFitnessTest(int $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $fitnessTest = $this->getFitnessTest($id);

                // Kiểm tra xem có đánh giá nào liên quan đến bài kiểm tra không
                $hasRecords = StudentFitnessRecord::where('fitness_test_id', $id)->exists();
                if ($hasRecords) {
                    throw new \Exception('Không thể xóa bài kiểm tra này vì đã có kết quả đánh giá liên quan', 422);
                }

                // Xóa thresholds (sẽ được xóa tự động với onDelete cascade, nhưng thực hiện rõ ràng)
                if ($fitnessTest->thresholds) {
                    $fitnessTest->thresholds->delete();
                }

                // Xóa bài kiểm tra
                $fitnessTest->delete();

                return true;
            });
        } catch (\Exception $e) {
            \Log::error('Lỗi khi xóa bài kiểm tra thể lực: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all assessment sessions, optionally filtered by current week.
     *
     * @param  bool  $currentWeekOnly  chỉ lấy tuần hiện tại
     * @return Collection danh sách các phiên đánh giá
     */
    public function getAssessmentSessions(bool $currentWeekOnly = false): Collection
    {
        $query = FitnessAssessmentSession::query()->orderByDesc('week_start_date');

        if ($currentWeekOnly) {
            $now = Carbon::now();
            $startOfWeek = $now->copy()->startOfWeek()->toDateString();
            $endOfWeek = $now->copy()->endOfWeek()->toDateString();

            // Instead of exact matching, check if the session falls within the current week
            // This improves test reliability with different time generation
            $query->whereDate('week_start_date', '>=', $startOfWeek)
                ->whereDate('week_end_date', '<=', $endOfWeek);

            \Log::debug('Querying current week sessions with dates', [
                'start_date' => $startOfWeek,
                'end_date' => $endOfWeek,
            ]);
        }

        $results = $query->get();

        // Debug count for tests
        if ($currentWeekOnly) {
            \Log::debug('Current week sessions count: '.$results->count());
        }

        return $results;
    }

    /**
     * Get or create assessment session for the current week.
     */
    public function getCurrentWeekSession(): FitnessAssessmentSession
    {
        return FitnessAssessmentSession::getCurrentWeekSession();
    }

    /**
     * Record a fitness assessment result for a student.
     *
     * @param  array  $data  dữ liệu đánh giá
     * @param  User  $manager  quản lý thực hiện đánh giá
     *
     * @throws \Exception nếu xảy ra lỗi
     */
    public function recordFitnessAssessment(array $data, User $manager): StudentFitnessRecord
    {
        try {
            return DB::transaction(function () use ($data, $manager) {
                // Lấy phiên đánh giá hiện tại nếu không được chỉ định
                $sessionId = $data['assessment_session_id'] ?? null;
                if (! $sessionId) {
                    $session = $this->getCurrentWeekSession();
                    $sessionId = $session->id;
                }

                // Kiểm tra học viên tồn tại và có vai trò student
                $student = User::find($data['user_id']);
                if (! $student || ! $student->isStudent()) {
                    throw new \Exception('Không tìm thấy học viên hợp lệ', 422);
                }

                // Kiểm tra bài kiểm tra thể lực tồn tại
                $fitnessTest = $this->getFitnessTest($data['fitness_test_id']);

                // Kiểm tra xem đã có đánh giá cho học viên này với bài kiểm tra này trong phiên đánh giá này chưa
                $existingRecord = StudentFitnessRecord::where('user_id', $data['user_id'])
                    ->where('fitness_test_id', $data['fitness_test_id'])
                    ->where('assessment_session_id', $sessionId)
                    ->first();

                if ($existingRecord) {
                    throw new \Exception('Học viên này đã được đánh giá cho bài kiểm tra này trong phiên đánh giá hiện tại', 422);
                }

                // Tạo bản ghi đánh giá mới
                $record = new StudentFitnessRecord([
                    'user_id' => $data['user_id'],
                    'manager_id' => $manager->id,
                    'fitness_test_id' => $data['fitness_test_id'],
                    'assessment_session_id' => $sessionId,
                    'performance' => $data['performance'],
                    'notes' => $data['notes'] ?? null,
                ]);

                // Tính toán xếp loại dựa trên performance
                $record->calculateRating();

                $record->save();

                return $record->load(['student', 'fitnessTest']);
            });
        } catch (\Exception $e) {
            \Log::error('Lỗi khi ghi nhận kết quả đánh giá thể lực: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get fitness assessments for a specific session.
     *
     * @param  int  $sessionId  mã phiên đánh giá
     * @param  int|null  $testId  optional mã bài kiểm tra để lọc
     * @return Collection danh sách kết quả đánh giá
     *
     * @throws \Exception nếu xảy ra lỗi
     */
    public function getSessionAssessments(int $sessionId, ?int $testId = null): Collection
    {
        try {
            $query = StudentFitnessRecord::with(['student', 'fitnessTest', 'manager'])
                ->where('assessment_session_id', $sessionId);

            if ($testId) {
                $query->where('fitness_test_id', $testId);
            }

            return $query->get();
        } catch (\Exception $e) {
            \Log::error('Lỗi khi lấy kết quả đánh giá thể lực: '.$e->getMessage());
            throw new \Exception('Không thể lấy kết quả đánh giá thể lực: '.$e->getMessage(), 500);
        }
    }

    /**
     * Ghi nhận kết quả đánh giá thể lực cho nhiều học viên cùng lúc
     * Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu
     *
     * @param  int  $fitnessTestId  mã bài kiểm tra thể lực
     * @param  array  $assessments  danh sách đánh giá (user_id, performance, notes)
     * @param  int|null  $sessionId  mã phiên đánh giá (nếu null sẽ sử dụng phiên hiện tại)
     * @param  User  $manager  người quản lý thực hiện đánh giá
     * @return array kết quả ghi nhận (success, failed)
     *
     * @throws \Exception nếu xảy ra lỗi
     */
    public function batchRecordFitnessAssessments(int $fitnessTestId, array $assessments, ?int $sessionId, User $manager): array
    {
        try {
            return DB::transaction(function () use ($fitnessTestId, $assessments, $sessionId, $manager) {
                // Lấy bài kiểm tra thể lực
                $fitnessTest = $this->getFitnessTest($fitnessTestId);

                // Lấy hoặc tạo phiên đánh giá
                if ($sessionId) {
                    $session = FitnessAssessmentSession::findOrFail($sessionId);
                } else {
                    $session = $this->getCurrentWeekSession();
                }

                $results = [
                    'success' => [],
                    'failed' => [],
                    'already_exists' => [],
                ];

                // Kiểm tra trước xem có bản ghi nào đã tồn tại không
                $existingRecords = [];
                $userIds = array_column($assessments, 'user_id');

                $existingEntries = StudentFitnessRecord::where('fitness_test_id', $fitnessTestId)
                    ->where('assessment_session_id', $session->id)
                    ->whereIn('user_id', $userIds)
                    ->get();

                if ($existingEntries->isNotEmpty()) {
                    foreach ($existingEntries as $entry) {
                        $existingRecords[$entry->user_id] = $entry;
                    }

                    // Nếu có bản ghi đã tồn tại, từ chối toàn bộ batch để tránh ghi một phần
                    if (count($existingRecords) > 0) {
                        $existingStudentIds = array_keys($existingRecords);
                        throw new \Exception('Một số học viên đã được đánh giá cho bài kiểm tra này trong phiên đánh giá hiện tại: '.
                            implode(', ', $existingStudentIds), 422);
                    }
                }

                // Kiểm tra học viên tồn tại và có vai trò hợp lệ
                $students = User::whereIn('id', $userIds)
                    ->where('role', User::ROLE_STUDENT)
                    ->get()
                    ->keyBy('id');

                foreach ($assessments as $assessment) {
                    $userId = $assessment['user_id'];

                    if (! isset($students[$userId])) {
                        $results['failed'][] = [
                            'user_id' => $userId,
                            'reason' => 'Không tìm thấy học viên hoặc không có vai trò học viên',
                        ];

                        continue;
                    }

                    // Tạo bản ghi đánh giá mới
                    $record = new StudentFitnessRecord([
                        'user_id' => $userId,
                        'manager_id' => $manager->id,
                        'fitness_test_id' => $fitnessTestId,
                        'assessment_session_id' => $session->id,
                        'performance' => $assessment['performance'],
                        'notes' => $assessment['notes'] ?? null,
                    ]);

                    // Tính toán xếp loại dựa trên performance
                    $record->calculateRating();
                    $record->save();

                    $results['success'][] = [
                        'user_id' => $userId,
                        'performance' => $assessment['performance'],
                        'rating' => $record->rating,
                    ];
                }

                // Nếu có bất kỳ lỗi nào, hủy bỏ toàn bộ transaction
                if (count($results['failed']) > 0) {
                    throw new \Exception('Có lỗi xảy ra trong quá trình đánh giá: '.
                        json_encode($results['failed']), 422);
                }

                // Thêm thông tin tổng hợp
                $results['message'] = 'Đã ghi nhận thành công '.count($results['success']).' kết quả đánh giá';

                return $results;
            });
        } catch (\Exception $e) {
            \Log::error('Lỗi khi ghi nhận hàng loạt kết quả đánh giá thể lực: '.$e->getMessage());
            throw $e;
        }
    }
}
