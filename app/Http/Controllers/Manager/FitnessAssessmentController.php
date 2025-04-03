<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\BaseController;
use App\Http\Requests\FitnessAssessmentRequest;
use App\Http\Requests\BatchFitnessAssessmentRequest;
use App\Services\FitnessTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FitnessAssessmentController extends BaseController
{
    protected $fitnessTestService;

    public function __construct(FitnessTestService $fitnessTestService)
    {
        $this->fitnessTestService = $fitnessTestService;
    }

    /**
     * lấy danh sách tất cả các phiên đánh giá thể lực
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllSessions(Request $request): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->getAssessmentSessions(
                $request->boolean('current_week_only', false)
            ),
            'Lấy danh sách phiên đánh giá thể lực thành công'
        );
    }

    /**
     * lấy hoặc tạo phiên đánh giá cho tuần hiện tại
     *
     * @return JsonResponse
     */
    public function getCurrentWeekSession(): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->getCurrentWeekSession(),
            'Lấy thông tin phiên đánh giá tuần hiện tại thành công'
        );
    }

    /**
     * lấy danh sách tất cả bài kiểm tra thể lực
     *
     * @return JsonResponse
     */
    public function getAllFitnessTests(): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->getAllFitnessTests(
                request()->input('per_page', 15),
                request()->input('page', 1)
            ),
            'Lấy danh sách bài kiểm tra thể lực thành công'
        );
    }

    /**
     * lấy kết quả đánh giá của một phiên đánh giá
     *
     * @param int $sessionId mã phiên đánh giá
     * @param Request $request
     * @return JsonResponse
     */
    public function getSessionAssessments(int $sessionId, Request $request): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->getSessionAssessments(
                $sessionId,
                $request->input('test_id')
            ),
            'Lấy danh sách kết quả đánh giá thể lực thành công'
        );
    }

    /**
     * ghi nhận kết quả đánh giá thể lực mới cho học viên
     *
     * @param FitnessAssessmentRequest $request
     * @return JsonResponse
     */
    public function recordAssessment(FitnessAssessmentRequest $request): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->recordFitnessAssessment(
                $request->validated(),
                auth()->user()
            ),
            'Ghi nhận kết quả đánh giá thể lực thành công',
            201
        );
    }

    /**
     * ghi nhận kết quả đánh giá thể lực hàng loạt cho nhiều học viên
     * dùng transaction
     *
     * @param BatchFitnessAssessmentRequest $request
     * @return JsonResponse
     */
    public function batchRecordAssessments(BatchFitnessAssessmentRequest $request): JsonResponse
    {
        return $this->executeService(
            fn () => $this->fitnessTestService->batchRecordFitnessAssessments(
                $request->fitness_test_id,
                $request->assessments,
                $request->assessment_session_id,
                auth()->user()
            ),
            'Ghi nhận hàng loạt kết quả đánh giá thể lực thành công',
            201
        );
    }
}