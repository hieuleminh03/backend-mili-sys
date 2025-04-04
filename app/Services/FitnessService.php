<?php

namespace App\Services;

use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use App\Models\FitnessAssessmentSession;
use App\Models\StudentFitnessRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FitnessService
{
    /**
     * Lấy tất cả các bài kiểm tra thể lực
     *
     * @return Collection danh sách bài kiểm tra
     */
    public function getAllTests(): Collection
    {
        return FitnessTest::with('thresholds')->orderBy('name')->get();
    }

    /**
     * Lấy thông tin của một bài kiểm tra
     *
     * @param int $id mã bài kiểm tra
     * @return FitnessTest
     * @throws \Exception nếu không tìm thấy
     */
    public function getTest(int $id): FitnessTest
    {
        $test = FitnessTest::with('thresholds')->findOrFail($id);
        return $test;
    }

    /**
     * Tạo mới bài kiểm tra thể lực
     *
     * @param array $data dữ liệu của bài kiểm tra
     * @return FitnessTest
     * @throws \Exception nếu có lỗi
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

                // Validate thresholds
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
     * Cập nhật bài kiểm tra thể lực
     *
     * @param int $id id của bài kiểm tra
     * @param array $data dữ liệu cần cập nhật
     * @return FitnessTest
     * @throws \Exception nếu có lỗi
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

                // Validate thresholds
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
     * Xóa một bài kiểm tra thể lực
     *
     * @param int $id id của bài kiểm tra
     * @return bool
     * @throws \Exception nếu không thể xóa
     */
    public function deleteTest(int $id): bool
    {
        return DB::transaction(function () use ($id) {
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
     * Lấy tất cả các phiên đánh giá (tuần)
     *
     * @param bool $currentOnly chỉ lấy phiên hiện tại
     * @return Collection
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
     * Lấy thông tin một phiên đánh giá thể lực
     *
     * @param int $id mã phiên đánh giá
     * @return FitnessAssessmentSession
     * @throws \Exception nếu không tìm thấy
     */
    public function getAssessmentSession(int $id): FitnessAssessmentSession
    {
        $session = FitnessAssessmentSession::findOrFail($id);
        return $session;
    }

    /**
     * Tạo mới phiên đánh giá thể lực
     *
     * @param array $data dữ liệu của phiên đánh giá
     * @return FitnessAssessmentSession
     * @throws \Exception nếu có lỗi
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

            // Đảm bảo rằng ngày bắt đầu là thứ 2 và ngày kết thúc là chủ nhật của tuần
            $startOfWeek = $startDate->copy()->startOfWeek();
            $endOfWeek = $startDate->copy()->endOfWeek();

            if (
                $startDate->format('Y-m-d') !== $startOfWeek->format('Y-m-d') ||
                $endDate->format('Y-m-d') !== $endOfWeek->format('Y-m-d')
            ) {
                throw new \Exception('Phiên đánh giá thể lực phải bao gồm một tuần đầy đủ (từ thứ 2 đến chủ nhật)', 422);
            }

            // Kiểm tra nếu đã có phiên đánh giá trong tuần đó
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
     * Cập nhật phiên đánh giá thể lực
     *
     * @param int $id mã phiên đánh giá
     * @param array $data dữ liệu cần cập nhật
     * @return FitnessAssessmentSession
     * @throws \Exception nếu có lỗi
     */
    public function updateAssessmentSession(int $id, array $data): FitnessAssessmentSession
    {
        return DB::transaction(function () use ($id, $data) {
            $session = FitnessAssessmentSession::findOrFail($id);

            // Kiểm tra xem có kết quả đánh giá nào trong phiên này không
            $hasRecords = StudentFitnessRecord::where('fitness_assessment_session_id', $id)->exists();

            // Chỉ cho phép cập nhật name và description nếu đã có dữ liệu đánh giá
            if ($hasRecords && (isset($data['start_date']) || isset($data['end_date']))) {
                throw new \Exception('Không thể thay đổi thời gian phiên đánh giá đã có dữ liệu ghi nhận', 422);
            }

            // Nếu cập nhật thời gian
            if (isset($data['start_date']) || isset($data['end_date'])) {
                $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : $session->start_date;
                $endDate = isset($data['end_date']) ? Carbon::parse($data['end_date']) : $session->end_date;

                // Kiểm tra ngày bắt đầu và kết thúc
                if ($startDate->gt($endDate)) {
                    throw new \Exception('Ngày bắt đầu phải trước ngày kết thúc', 422);
                }

                // Đảm bảo rằng ngày bắt đầu là thứ 2 và ngày kết thúc là chủ nhật của tuần
                $startOfWeek = $startDate->copy()->startOfWeek();
                $endOfWeek = $startDate->copy()->endOfWeek();

                if (
                    $startDate->format('Y-m-d') !== $startOfWeek->format('Y-m-d') ||
                    $endDate->format('Y-m-d') !== $endOfWeek->format('Y-m-d')
                ) {
                    throw new \Exception('Phiên đánh giá thể lực phải bao gồm một tuần đầy đủ (từ thứ 2 đến chủ nhật)', 422);
                }

                // Kiểm tra nếu đã có phiên đánh giá trong tuần đó (ngoài phiên hiện tại)
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

            // Cập nhật các thông tin khác
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
     * Xóa một phiên đánh giá thể lực
     * 
     * @param int $id mã phiên đánh giá
     * @return bool
     * @throws \Exception nếu không thể xóa
     */
    public function deleteAssessmentSession(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $session = FitnessAssessmentSession::findOrFail($id);

            // Kiểm tra xem có bản ghi đánh giá nào thuộc phiên này không
            $hasRecords = StudentFitnessRecord::where('fitness_assessment_session_id', $id)->exists();
            if ($hasRecords) {
                throw new \Exception('Không thể xóa phiên đánh giá này vì đã có dữ liệu đánh giá được ghi nhận', 422);
            }

            return (bool) $session->delete();
        });
    }

    /**
     * Lấy danh sách bản ghi thể lực của học viên trong một phiên đánh giá
     * 
     * @param int $sessionId mã phiên đánh giá
     * @param array $filters các bộ lọc (student_id, test_id, etc.)
     * @param int $perPage số kết quả mỗi trang
     * @return LengthAwarePaginator
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
     * Thêm/cập nhật kết quả kiểm tra thể lực cho học viên
     * 
     * @param array $data dữ liệu bản ghi
     * @return StudentFitnessRecord
     * @throws \Exception nếu có lỗi hoặc không thể cập nhật
     */
    public function saveStudentRecord(array $data): StudentFitnessRecord
    {
        return DB::transaction(function () use ($data) {
            // Kiểm tra xem bản ghi đã tồn tại chưa
            $existingRecord = StudentFitnessRecord::where([
                'student_id' => $data['student_id'],
                'fitness_test_id' => $data['fitness_test_id'],
                'fitness_assessment_session_id' => $data['session_id'],
            ])->first();

            // Nếu đã tồn tại, không cho phép cập nhật
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
     * Xóa một bản ghi thể lực của học viên
     * 
     * @param int $recordId mã bản ghi
     * @return bool
     */
    public function deleteStudentRecord(int $recordId): bool
    {
        $record = StudentFitnessRecord::findOrFail($recordId);
        return (bool) $record->delete();
    }

    /**
     * Lấy báo cáo tổng hợp kết quả thể lực của một học viên
     * 
     * @param int $studentId mã học viên
     * @param int|null $sessionId mã phiên đánh giá (null = tất cả phiên)
     * @return array
     */
    public function getStudentFitnessReport(int $studentId, ?int $sessionId = null): array
    {
        $query = StudentFitnessRecord::with(['fitnessTest', 'assessmentSession'])
            ->where('student_id', $studentId);

        if ($sessionId) {
            $query->where('fitness_assessment_session_id', $sessionId);
        }

        $records = $query->get();

        // Nhóm theo phiên đánh giá
        $reportBySession = [];
        foreach ($records as $record) {
            $sessionId = $record->fitness_assessment_session_id;

            if (!isset($reportBySession[$sessionId])) {
                $reportBySession[$sessionId] = [
                    'session' => $record->assessmentSession,
                    'records' => [],
                    'summary' => [
                        'excellent' => 0,
                        'good' => 0,
                        'pass' => 0,
                        'fail' => 0,
                        'not_rated' => 0,
                        'total' => 0
                    ]
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
                'total' => $records->count()
            ]
        ];
    }

    /**
     * Lấy hoặc tạo phiên đánh giá thể lực cho tuần hiện tại
     * 
     * @return FitnessAssessmentSession
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

        if (!$session) {
            // Tạo phiên mới cho tuần hiện tại nếu chưa có
            $weekNumber = $now->weekOfYear;
            $year = $now->year;

            $session = FitnessAssessmentSession::create([
                'name' => "Tuần {$weekNumber} năm {$year}",
                'start_date' => $startOfWeek,
                'end_date' => $endOfWeek,
                'description' => "Phiên đánh giá thể lực tuần {$weekNumber} năm {$year}"
            ]);
        }

        return $session;
    }

    /**
     * Lấy danh sách bài kiểm tra thể lực kèm theo thông tin đánh giá trong phiên hiện tại
     * 
     * @return array
     */
    public function getTestsForCurrentSession(): array
    {
        // Lấy phiên hiện tại
        $currentSession = $this->getCurrentWeekSession();

        // Lấy tất cả bài kiểm tra
        $tests = FitnessTest::with('thresholds')->orderBy('name')->get();

        // Lấy tất cả kết quả đánh giá trong phiên này
        $records = StudentFitnessRecord::where('fitness_assessment_session_id', $currentSession->id)
            ->with(['student'])
            ->get()
            ->groupBy('fitness_test_id');

        $result = [
            'session' => $currentSession,
            'tests' => []
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
                })
            ];

            $result['tests'][] = $testData;
        }

        return $result;
    }
}
