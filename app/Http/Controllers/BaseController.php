<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as LaravelBaseController;

abstract class BaseController extends LaravelBaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * trả về một response thành công
     *
     * @param  mixed  $data  dữ liệu trả về
     * @param  string|null  $message  thông báo kèm
     * @param  int  $code  mã trạng thái
     */
    protected function successResponse($data = null, string $message = 'success', int $code = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
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
     * @param  string  $message  thông báo
     * @param  mixed  $errors  thông báo lỗi cụ thể
     * @param  int  $code  mã trạng thái
     */
    protected function errorResponse(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * thực thi service và bọc các exception
     * wrapper cho các service method, để handle cụ thể các message nếu có
     *
     * @param  callable  $serviceCallback  hàm callback gọi service
     * @param  string  $successMessage  thông báo thành công
     * @param  int  $successStatusCode  mã trạng thái thành công
     */
    protected function executeService(callable $serviceCallback, string $successMessage = 'Lấy dữ liệu thành công', int $successStatusCode = 200): JsonResponse
    {
        try {
            $data = $serviceCallback();

            return $this->successResponse($data, $successMessage, $successStatusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Lỗi validation: '.$e->getMessage(), ['errors' => $e->errors()]);

            return $this->errorResponse(
                'Dữ liệu không hợp lệ',
                $e->errors(),
                422
            );
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Lỗi database: '.$e->getMessage());

            // Kiểm tra lỗi unique constraint
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->errorResponse(
                    'Dữ liệu đã tồn tại',
                    ['name' => ['Tên học kỳ đã tồn tại trong hệ thống']],
                    422
                );
            }

            return $this->errorResponse(
                'Lỗi database',
                ['database' => [$e->getMessage()]],
                500
            );
        } catch (\Exception $e) {
            \Log::error('Lỗi không xác định: '.$e->getMessage());

            return $this->errorResponse(
                $e->getMessage(),
                ['error' => [$e->getMessage()]],
                $this->determineStatusCode($e)
            );
        }
    }

    /**
     * check class của exception để lấy code trạng thái tương ứng
     *
     * @param  Exception  $e  ngoại lệ
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
     * @param  callable  $callback  hàm callback
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
     * @param  int  $defaultPerPage  số lượng mục mặc định trên một trang
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
