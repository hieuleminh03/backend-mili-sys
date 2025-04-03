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
        $test = FitnessTest::with('thresholds')->find($id);
        
        if (!$test) {
            throw new \Exception('Không tìm thấy bài kiểm tra thể lực', 422);
        }
        
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
