<?php

namespace App\Services;

use App\Models\FitnessAssessmentSession;
use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use App\Models\StudentFitnessRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FitnessService
{
    /**
     * Lấy tất cả các bài kiểm tra thể lực
     */
    public function getAllTests(): Collection
    {
        return FitnessTest::with('thresholds')->orderBy('name')->get();
    }

    /**
     * Lấy thông tin chi tiết của một bài kiểm tra
     *
     * @param  int  $id  ID bài kiểm tra
     *
     * @throws \Exception Khi không tìm thấy bài kiểm tra
     */
    public function getTest(int $id): FitnessTest
    {
        $test = FitnessTest::with('thresholds')->findOrFail($id);

        return $test;
    }

    /**
     * Tạo mới bài kiểm tra thể lực
     *
     * @param  array  $data  Dữ liệu bài kiểm tra
     *
     * @throws \Exception Khi validate thất bại
     */
    public function createTest(array $data): FitnessTest
    {
        return DB::transaction(function () use ($data) {
            // Tạo bài kiểm tra
            $test = FitnessTest::create([
                'name' => $data['name'],
                'unit' => $data['unit'],
                'higher_is_better' => $data['higher_is_better'] ?? false,
            ]);

            // Tạo ngưỡng đánh giá
            if (isset($data['excellent_threshold']) && isset($data['good_threshold']) && isset($data['pass_threshold'])) {
                $thresholds = new FitnessTestThreshold([
                    'fitness_test_id' => $test->id,
                    'excellent_threshold' => $data['excellent_threshold'],
                    'good_threshold' => $data['good_threshold'],
                    'pass_threshold' => $data['pass_threshold'],
                ]);

                // Validate ngưỡng đánh giá
                $validationResult = $thresholds->validateThresholdOrder();
                if ($validationResult !== true) {
                    throw new \Exception(implode(', ', $validationResult));
                }

                $thresholds->save();
                $test->load('thresholds');
            }

            return $test;
        });
    }

    /**
     * Cập nhật thông tin bài kiểm tra thể lực
     *
     * @param  int  $id  ID bài kiểm tra
     * @param  array  $data  Dữ liệu cập nhật
     *
     * @throws \Exception Khi validate thất bại
     */
    public function updateTest(int $id, array $data): FitnessTest
    {
        return DB::transaction(function () use ($id, $data) {
            $test = FitnessTest::with('thresholds')->findOrFail($id);

            // Cập nhật thông tin bài kiểm tra
            if (isset($data['name'])) {
                $test->name = $data['name'];
            }

            if (isset($data['unit'])) {
                $test->unit = $data['unit'];
            }

            if (isset($data['higher_is_better'])) {
                $test->higher_is_better = $data['higher_is_better'];
            }

            $test->save();

            // Cập nhật hoặc tạo mới ngưỡng đánh giá
            if (isset($data['excellent_threshold']) || isset($data['good_threshold']) || isset($data['pass_threshold'])) {
                $thresholds = $test->thresholds ?? new FitnessTestThreshold(['fitness_test_id' => $test->id]);

                if (isset($data['excellent_threshold'])) {
                    $thresholds->excellent_threshold = $data['excellent_threshold'];
                }

                if (isset($data['good_threshold'])) {
                    $thresholds->good_threshold = $data['good_threshold'];
                }

                if (isset($data['pass_threshold'])) {
                    $thresholds->pass_threshold = $data['pass_threshold'];
                }

                // Validate ngưỡng đánh giá
                $validationResult = $thresholds->validateThresholdOrder();
                if ($validationResult !== true) {
                    throw new \Exception(implode(', ', $validationResult));
                }

                $thresholds->save();
                $test->load('thresholds');
            }

            return $test;
        });
    }

    /**
     * Xóa bài kiểm tra thể lực
     *
     * @param  int  $id  ID bài kiểm tra
     *
     * @throws \Exception Khi bài kiểm tra đã có dữ liệu đánh giá
     */
    public function deleteTest(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            // Truy vấn trực tiếp từ Model thay vì gọi getTest để tránh lỗi return type
            $test = FitnessTest::findOrFail($id);

            // Kiểm tra xem có bản ghi đánh giá nào dùng bài test này không
            $hasRecords = StudentFitnessRecord::where('fitness_test_id', $id)->exists();
            if ($hasRecords) {
                throw new \Exception('Không thể xóa bài kiểm tra này vì đã có dữ liệu đánh giá được ghi nhận', 422);
            }

            return (bool) $test->delete();
        });
    }

    /**
     * Lấy danh sách các phiên đánh giá thể lực
     *
     * @param  bool  $currentOnly  Chỉ lấy phiên đánh giá hiện tại
     */
    public function getAllAssessmentSessions(bool $currentOnly = false): Collection
    {
        $query = FitnessAssessmentSession::query()->orderBy('start_date', 'desc');

        if ($currentOnly) {
            $now = Carbon::now();
            $query->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now);
        }

        return $query->get();
    }

    /**
     * Lấy thông tin chi tiết của một phiên đánh giá
     *
     * @param  int  $id  ID phiên đánh giá
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Khi không tìm thấy
     */
    public function getAssessmentSession(int $id): FitnessAssessmentSession
    {
        $session = FitnessAssessmentSession::findOrFail($id);

        return $session;
    }

    /**
     * Tạo mới phiên đánh giá thể lực
     *
     * @param  array  $data  Dữ liệu phiên đánh giá
     *
     * @throws \Exception Khi validate thất bại hoặc phiên đánh giá trùng lặp
     */
    public function createAssessmentSession(array $data): FitnessAssessmentSession
    {
        return DB::transaction(function () use ($data) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            // Kiểm tra ngày bắt đầu và kết thúc
            if ($startDate->gt($endDate)) {
                throw new \Exception('Ngày bắt đầu phải trước ngày kết thúc', 422);
            }

            // Đảm bảo phiên đánh giá là một tuần đầy đủ
            $startOfWeek = $startDate->copy()->startOfWeek();
            $endOfWeek = $startDate->copy()->endOfWeek();

            if (
                $startDate->format('Y-m-d') !== $startOfWeek->format('Y-m-d') ||
                $endDate->format('Y-m-d') !== $endOfWeek->format('Y-m-d')
            ) {
                throw new \Exception('Phiên đánh giá thể lực phải bao gồm một tuần đầy đủ (từ thứ 2 đến chủ nhật)', 422);
            }

            // Kiểm tra trùng lặp phiên đánh giá
            $existingWeekSession = FitnessAssessmentSession::where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '=', $startDate)
                    ->where('end_date', '=', $endDate);
            })->first();

            if ($existingWeekSession) {
                throw new \Exception('Phiên đánh giá thể lực cho tuần này đã tồn tại', 422);
            }

            // Tạo phiên đánh giá mới
            return FitnessAssessmentSession::create([
                'name' => $data['name'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'description' => $data['description'] ?? null,
            ]);
        });
    }

    /**
     * Cập nhật thông tin phiên đánh giá thể lực
     *
     * @param  int  $id  ID phiên đánh giá
     * @param  array  $data  Dữ liệu cập nhật
     *
     * @throws \Exception Khi validate thất bại hoặc phiên đánh giá đã có dữ liệu
     */
    public function updateAssessmentSession(int $id, array $data): FitnessAssessmentSession
    {
        return DB::transaction(function () use ($id, $data) {
            $session = FitnessAssessmentSession::findOrFail($id);

            // Kiểm tra dữ liệu đánh giá đã tồn tại
            $hasRecords = StudentFitnessRecord::where('fitness_assessment_session_id', $id)->exists();

            // Giới hạn cập nhật nếu đã có dữ liệu
            if ($hasRecords && (isset($data['start_date']) || isset($data['end_date']))) {
                throw new \Exception('Không thể thay đổi thời gian phiên đánh giá đã có dữ liệu ghi nhận', 422);
            }

            // Xử lý cập nhật thời gian
            if (isset($data['start_date']) || isset($data['end_date'])) {
                $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : $session->start_date;
                $endDate = isset($data['end_date']) ? Carbon::parse($data['end_date']) : $session->end_date;

                // Kiểm tra thứ tự thời gian
                if ($startDate->gt($endDate)) {
                    throw new \Exception('Ngày bắt đầu phải trước ngày kết thúc', 422);
                }

                // Đảm bảo phiên đánh giá là một tuần đầy đủ
                $startOfWeek = $startDate->copy()->startOfWeek();
                $endOfWeek = $startDate->copy()->endOfWeek();

                if (
                    $startDate->format('Y-m-d') !== $startOfWeek->format('Y-m-d') ||
                    $endDate->format('Y-m-d') !== $endOfWeek->format('Y-m-d')
                ) {
                    throw new \Exception('Phiên đánh giá thể lực phải bao gồm một tuần đầy đủ (từ thứ 2 đến chủ nhật)', 422);
                }

                // Kiểm tra trùng lặp với phiên khác
                $existingWeekSession = FitnessAssessmentSession::where('id', '!=', $id)
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '=', $startDate)
                            ->where('end_date', '=', $endDate);
                    })->first();

                if ($existingWeekSession) {
                    throw new \Exception('Phiên đánh giá thể lực cho tuần này đã tồn tại', 422);
                }

                $session->start_date = $startDate;
                $session->end_date = $endDate;
            }

            // Cập nhật thông tin cơ bản
            if (isset($data['name'])) {
                $session->name = $data['name'];
            }

            if (isset($data['description'])) {
                $session->description = $data['description'];
            }

            $session->save();

            return $session;
        });
    }

    /**
     * Xóa phiên đánh giá thể lực
     *
     * @param  int  $id  ID phiên đánh giá
     *
     * @throws \Exception Khi phiên đánh giá đã có dữ liệu
     */
    public function deleteAssessmentSession(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $session = FitnessAssessmentSession::findOrFail($id);

            // Kiểm tra dữ liệu đánh giá đã tồn tại
            $hasRecords = StudentFitnessRecord::where('fitness_assessment_session_id', $id)->exists();
            if ($hasRecords) {
                throw new \Exception('Không thể xóa phiên đánh giá này vì đã có dữ liệu đánh giá được ghi nhận', 422);
            }

            return (bool) $session->delete();
        });
    }

    /**
     * Lấy danh sách kết quả đánh giá thể lực trong một phiên
     *
     * @param  int  $sessionId  ID phiên đánh giá
     * @param  array  $filters  Các bộ lọc (student_id, test_id, result_min, result_max, rating)
     * @param  int  $perPage  Số kết quả trên mỗi trang
     */
    public function getStudentRecords(int $sessionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StudentFitnessRecord::with(['student', 'fitnessTest', 'assessmentSession'])
            ->where('fitness_assessment_session_id', $sessionId);

        // Áp dụng các bộ lọc
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['test_id'])) {
            $query->where('fitness_test_id', $filters['test_id']);
        }

        if (isset($filters['result_min'])) {
            $query->where('result', '>=', $filters['result_min']);
        }

        if (isset($filters['result_max'])) {
            $query->where('result', '<=', $filters['result_max']);
        }

        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Lưu kết quả kiểm tra thể lực cho học viên
     *
     * @param  array  $data  Dữ liệu kết quả đánh giá
     *
     * @throws \Exception Khi kết quả đã tồn tại hoặc validate thất bại
     */
    public function saveStudentRecord(array $data): StudentFitnessRecord
    {
        return DB::transaction(function () use ($data) {
            // Kiểm tra kết quả đã tồn tại
            $existingRecord = StudentFitnessRecord::where([
                'student_id' => $data['student_id'],
                'fitness_test_id' => $data['fitness_test_id'],
                'fitness_assessment_session_id' => $data['session_id'],
            ])->first();

            // Không cho phép cập nhật kết quả đã lưu
            if ($existingRecord) {
                throw new \Exception('Kết quả đánh giá thể lực đã được lưu trước đó và không thể thay đổi', 422);
            }

            // Tạo mới bản ghi
            $record = new StudentFitnessRecord([
                'student_id' => $data['student_id'],
                'fitness_test_id' => $data['fitness_test_id'],
                'fitness_assessment_session_id' => $data['session_id'],
            ]);

            // Lấy thông tin bài kiểm tra để đánh giá
            $test = FitnessTest::with('thresholds')->findOrFail($data['fitness_test_id']);
            $result = $data['result'];

            // Tự động đánh giá dựa trên ngưỡng
            if ($test->thresholds) {
                if ($test->higher_is_better) {
                    if ($result >= $test->thresholds->excellent_threshold) {
                        $rating = 'excellent';
                    } elseif ($result >= $test->thresholds->good_threshold) {
                        $rating = 'good';
                    } elseif ($result >= $test->thresholds->pass_threshold) {
                        $rating = 'pass';
                    } else {
                        $rating = 'fail';
                    }
                } else {
                    if ($result <= $test->thresholds->excellent_threshold) {
                        $rating = 'excellent';
                    } elseif ($result <= $test->thresholds->good_threshold) {
                        $rating = 'good';
                    } elseif ($result <= $test->thresholds->pass_threshold) {
                        $rating = 'pass';
                    } else {
                        $rating = 'fail';
                    }
                }
            } else {
                $rating = 'not_rated';
            }

            // Cập nhật dữ liệu
            $record->result = $result;
            $record->rating = $rating;
            $record->notes = $data['notes'] ?? null;
            $record->recorded_by = auth()->id();
            $record->save();

            return $record;
        });
    }

    /**
     * Xóa kết quả đánh giá thể lực của học viên
     *
     * @param  int  $recordId  ID bản ghi
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Khi không tìm thấy
     */
    public function deleteStudentRecord(int $recordId): bool
    {
        $record = StudentFitnessRecord::findOrFail($recordId);

        return (bool) $record->delete();
    }

    /**
     * Lấy báo cáo tổng hợp kết quả thể lực của học viên
     *
     * @param  int  $studentId  ID học viên
     * @param  int|null  $sessionId  ID phiên đánh giá (null = tất cả phiên)
     * @return array Báo cáo kết quả theo phiên và tổng hợp
     */
    public function getStudentFitnessReport(int $studentId, ?int $sessionId = null): array
    {
        $query = StudentFitnessRecord::with(['fitnessTest', 'assessmentSession'])
            ->where('student_id', $studentId);

        if ($sessionId) {
            $query->where('fitness_assessment_session_id', $sessionId);
        }

        $records = $query->get();

        // Nhóm kết quả theo phiên đánh giá
        $reportBySession = [];
        foreach ($records as $record) {
            $sessionId = $record->fitness_assessment_session_id;

            if (! isset($reportBySession[$sessionId])) {
                $reportBySession[$sessionId] = [
                    'session' => $record->assessmentSession,
                    'records' => [],
                    'summary' => [
                        'excellent' => 0,
                        'good' => 0,
                        'pass' => 0,
                        'fail' => 0,
                        'not_rated' => 0,
                        'total' => 0,
                    ],
                ];
            }

            $reportBySession[$sessionId]['records'][] = $record;
            $reportBySession[$sessionId]['summary'][$record->rating]++;
            $reportBySession[$sessionId]['summary']['total']++;
        }

        // Lấy thông tin học viên
        $student = User::findOrFail($studentId);

        return [
            'student' => $student,
            'sessions' => $reportBySession,
            'overall_summary' => [
                'excellent' => $records->where('rating', 'excellent')->count(),
                'good' => $records->where('rating', 'good')->count(),
                'pass' => $records->where('rating', 'pass')->count(),
                'fail' => $records->where('rating', 'fail')->count(),
                'not_rated' => $records->where('rating', 'not_rated')->count(),
                'total' => $records->count(),
            ],
        ];
    }

    /**
     * Lấy hoặc tạo phiên đánh giá thể lực cho tuần hiện tại
     */
    public function getCurrentWeekSession(): FitnessAssessmentSession
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek()->startOfDay(); // Thứ 2
        $endOfWeek = $now->copy()->endOfWeek()->endOfDay();       // Chủ nhật

        $session = FitnessAssessmentSession::where(function ($query) use ($startOfWeek, $endOfWeek) {
            $query->where('start_date', '=', $startOfWeek)
                ->where('end_date', '=', $endOfWeek);
        })->first();

        if (! $session) {
            // Tạo phiên mới cho tuần hiện tại
            $weekNumber = $now->weekOfYear;
            $year = $now->year;

            $session = FitnessAssessmentSession::create([
                'name' => "Tuần {$weekNumber} năm {$year}",
                'start_date' => $startOfWeek,
                'end_date' => $endOfWeek,
                'description' => "Phiên đánh giá thể lực tuần {$weekNumber} năm {$year}",
            ]);
        }

        return $session;
    }

    /**
     * Lấy danh sách bài kiểm tra kèm kết quả trong phiên hiện tại
     *
     * @return array Thông tin phiên và danh sách bài kiểm tra với kết quả
     */
    public function getTestsForCurrentSession(): array
    {
        // Lấy phiên hiện tại
        $currentSession = $this->getCurrentWeekSession();

        // Lấy tất cả bài kiểm tra
        $tests = FitnessTest::with('thresholds')->orderBy('name')->get();

        // Lấy kết quả đánh giá trong phiên hiện tại
        $records = StudentFitnessRecord::where('fitness_assessment_session_id', $currentSession->id)
            ->with(['student'])
            ->get()
            ->groupBy('fitness_test_id');

        $result = [
            'session' => $currentSession,
            'tests' => [],
        ];

        foreach ($tests as $test) {
            $testRecords = $records->get($test->id, collect([]));
            $testData = [
                'id' => $test->id,
                'name' => $test->name,
                'unit' => $test->unit,
                'higher_is_better' => $test->higher_is_better,
                'thresholds' => $test->thresholds,
                'records_count' => $testRecords->count(),
                'assessed_students' => $testRecords->map(function ($record) {
                    return [
                        'student_id' => $record->student_id,
                        'student_name' => $record->student->name ?? 'Unknown',
                        'result' => $record->result,
                        'rating' => $record->rating,
                    ];
                }),
            ];

            $result['tests'][] = $testData;
        }

        return $result;
    }
}
