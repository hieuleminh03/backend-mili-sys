<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as LaravelBaseController;
use Exception;

abstract class BaseController extends LaravelBaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    /**
     * trả về một response thành công
     *
     * @param mixed $data dữ liệu trả về
     * @param string|null $message thông báo kèm
     * @param int $code mã trạng thái
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = "success", int $code = 200): JsonResponse
    {
        $response = [
            'status' => 'success'
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $code);
    }
    
    /**
     * trả về một response thất bại
     *
     * @param string $message thông báo
     * @param mixed $errors thông báo lỗi cụ thể
     * @param int $code mã trạng thái
     * @return JsonResponse
     */
    protected function errorResponse(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $code);
    }
    
    /**
     * wrapper cho các service method, để handle cụ thể các message nếu có
     *
     * @param callable $callback hàm callback (service method)
     * @param string $successMessage thông báo thành công
     * @param int $successCode mã trạng thái thành công
     * @return JsonResponse
     */
    protected function executeService(callable $callback, string $successMessage = '', int $successCode = 200): JsonResponse
    {
        try {
            $result = call_user_func($callback);
            return $this->successResponse($result, $successMessage, $successCode);
        } catch (Exception $e) {
            $code = $this->determineStatusCode($e);
            return $this->errorResponse($e->getMessage(), null, $code);
        }
    }
    
    /**
     * check class của exception để lấy code trạng thái tương ứng
     *
     * @param Exception $e ngoại lệ
     * @return int mã trạng thái
     */
    protected function determineStatusCode(Exception $e): int
    {
        // JWT specific exceptions
        if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
            return 401; // token không hợp lệ
        }
        
        if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
            return 401; // token đã hết hạn
        }
        
        if ($e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
            return 401; // token không được cung cấp hoặc không thể phân tích
        }
        
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }
        
        // các trường hợp cụ thể khác
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 404; // tài nguyên không tồn tại
        }
        
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403; // hành động này không được phép
        }
        
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return 422; // lỗi validation
        }
        
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return 404; // không tìm thấy
        }
        
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return 405; // phương thức không được phép
        }
        
        return 400; // lỗi request (mặc định)
    }
    
    /**
     * thực hiện một database transaction với callback được cung cấp
     *
     * @param callable $callback hàm callback
     * @return mixed kết quả từ callback
     */
    protected function transaction(callable $callback)
    {
        return \DB::transaction(function () use ($callback) {
            return call_user_func($callback);
        });
    }
    
    /**
     * lấy tham số phân trang chuẩn từ request
     *
     * @param int $defaultPerPage số lượng mục mặc định trên một trang
     * @return array tham số phân trang
     */
    protected function getPaginationParams(int $defaultPerPage = 15): array
    {
        return [
            'page' => request()->input('page', 1),
            'per_page' => request()->input('per_page', $defaultPerPage),
        ];
    }
}
