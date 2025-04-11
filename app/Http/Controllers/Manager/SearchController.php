<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\BaseController;
use App\Http\Requests\SearchStudentRequest;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends BaseController
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * tìm kiếm sinh viên theo tên hoặc email
     * nếu không có query thì trả về tất cả sinh viên
     *
     * @param  SearchStudentRequest  $request  dữ liệu tìm kiếm
     * @return JsonResponse danh sách sinh viên phù hợp
     */
    public function searchStudents(SearchStudentRequest $request): JsonResponse
    {
        $query = $request->has('query') ? $request->input('query') : null;

        return $this->executeService(
            fn () => $this->searchService->searchStudents($query),
            'Tìm kiếm học viên thành công'
        );
    }
}
