<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * danh sách các exception và level log tương ứng
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * danh sách các exception không apply log
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * danh sách các input không flash vào session trong trường hợp validation exception
     * phần này đảm bảo privacy cho các input này
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * cài các callback để process cho các exception
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // xử lý tất cả exception để api luôn trả về json
        $this->renderable(function (Throwable $e, $request) {
            // Chắc chắn đây là API request
            // if (!$request->wantsJson() && !$request->is('api/*')) {
            //     return null; // Chuyển cho Laravel xử lý exception không phải API
            // }

            // Mã lỗi mặc định
            $status = 400;

            // Nếu có code và là HTTP code (400-599), sử dụng code đó
            if (method_exists($e, 'getCode') && $e->getCode() >= 400 && $e->getCode() < 600) {
                $status = $e->getCode();
            }

            // Handle thêm cho các business logic error với mã 422
            if ($e instanceof \Exception &&
                ! ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) &&
                (strpos($e->getMessage(), 'đã được đánh giá') !== false ||
                 strpos($e->getMessage(), 'đã tồn tại') !== false)
            ) {
                $status = 422;
            }

            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];

            // Authentication exceptions
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                $status = 401;
                $response['message'] = 'Unauthenticated';
            }

            // Authorization exceptions
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                $status = 403;
                $response['message'] = 'This action is not allowed';
            }

            // Resource not found
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $status = 404;
                // Xử lý thông điệp dựa trên model
                $modelClass = $e->getModel();
                $modelName = class_basename($modelClass);

                // Map model name to Vietnamese
                $modelMap = [
                    'Term' => 'học kỳ',
                    'Course' => 'khóa học',
                    'User' => 'người dùng',
                    'StudentCourse' => 'đăng ký học phần',
                ];

                $friendlyName = $modelMap[$modelName] ?? strtolower($modelName);

                // Đặc biệt xử lý term_id không tồn tại
                if ($modelName === 'Term') {
                    $status = 422; // Đổi thành 422 thay vì 404 để client hiểu đây là lỗi validation
                    $response['errors'] = ['term_id' => ['Kỳ học không tồn tại trong hệ thống']];
                } elseif ($modelName === 'StudentCourse') {
                    $status = 422;
                    $response['errors'] = ['enrollment' => ['Không tìm thấy đăng ký học phần']];
                }

                $response['message'] = "Không tìm thấy {$friendlyName} với ID đã cung cấp";
            }

            // Route not found
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                $status = 404;
                $response['message'] = 'Không tìm thấy API endpoint này';
            }

            // Type errors (including term_id empty string conversion)
            if ($e instanceof \TypeError) {
                $status = 422;
                $response['message'] = 'Kiểu dữ liệu không hợp lệ';
                if (strpos($e->getMessage(), 'term_id') !== false) {
                    $response['errors'] = ['term_id' => ['Mã kỳ học phải là số nguyên']];
                } else {
                    $response['errors'] = ['input' => ['Một số trường dữ liệu không đúng định dạng']];
                }
            }

            // Validation errors
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $status = 422;
                $response['message'] = 'Lỗi dữ liệu đầu vào';
                $response['errors'] = $e->errors();
            }

            // Method not allowed
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                $status = 405;
                $response['message'] = 'Method not allowed';
            }

            // Database Query exceptions
            if ($e instanceof \Illuminate\Database\QueryException) {
                $status = 500;
                $response['message'] = 'Database error';

                // Kiểm tra nếu là lỗi unique constraint
                if ($e->getCode() == 23000) {
                    $status = 422;

                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $response['message'] = 'Dữ liệu đã tồn tại';

                        // Tìm table và column bị duplicate
                        if (strpos($e->getMessage(), 'terms_name_unique') !== false) {
                            $response['errors'] = ['name' => ['Tên học kỳ đã tồn tại trong hệ thống']];
                        } elseif (strpos($e->getMessage(), 'courses_code_unique') !== false) {
                            $response['errors'] = ['code' => ['Mã lớp học đã tồn tại trong hệ thống']];
                        } else {
                            $response['errors'] = ['duplicate' => ['Dữ liệu bị trùng lặp']];
                        }
                    } elseif (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                        $response['message'] = 'Dữ liệu liên kết không tồn tại';

                        if (strpos($e->getMessage(), 'term_id') !== false) {
                            $response['errors'] = ['term_id' => ['Kỳ học không tồn tại trong hệ thống']];
                        } else {
                            $response['errors'] = ['foreign_key' => ['Dữ liệu liên kết không tồn tại']];
                        }
                    }
                }
            }

            // JWT specific exceptions
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                $status = 401;
                $response['message'] = 'Token is invalid';
            }

            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                $status = 401;
                $response['message'] = 'Token has expired';
            }

            if ($e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
                $status = 401;
                $response['message'] = 'Token is not provided or could not be parsed';
            }

            // Standard exceptions với term_id và các lỗi nghiệp vụ khác
            if ($e instanceof \Exception && ! ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException)) {
                if (strpos($e->getMessage(), 'Kỳ học không tồn tại') !== false) {
                    $status = 422;
                    $response['errors'] = ['term_id' => ['Kỳ học không tồn tại trong hệ thống']];
                } elseif (strpos($e->getMessage(), 'Không thể thay đổi kỳ học') !== false) {
                    $status = 422;
                    $response['errors'] = ['term_id' => ['Không thể thay đổi kỳ học sau khi đã tạo lớp']];
                } elseif (strpos($e->getMessage(), 'Không thể xóa lớp học') !== false) {
                    $status = 422;
                    $response['errors'] = ['course' => ['Không thể xóa lớp học vì có sinh viên đã đăng ký']];
                } elseif (strpos($e->getMessage(), 'Thời gian đăng ký đã kết thúc') !== false) {
                    $status = 422;
                    $response['errors'] = ['enrollment' => ['Thời gian đăng ký đã kết thúc cho kỳ học này']];
                } elseif (strpos($e->getMessage(), 'Lớp học đã đạt giới hạn đăng ký tối đa') !== false) {
                    $status = 422;
                    $response['errors'] = ['enrollment' => ['Lớp học đã đạt giới hạn đăng ký tối đa']];
                } elseif (strpos($e->getMessage(), 'Giới hạn đăng ký không thể nhỏ hơn số sinh viên') !== false) {
                    $status = 422;
                    $response['errors'] = ['enroll_limit' => [$e->getMessage()]];
                } elseif (strpos($e->getMessage(), 'Sinh viên đã đăng ký') !== false) {
                    $status = 422;
                    $response['errors'] = ['enrollment' => ['Sinh viên đã đăng ký lớp học này']];
                } elseif (strpos($e->getMessage(), 'Thời gian nhập điểm chưa bắt đầu') !== false) {
                    $status = 422;
                    $response['errors'] = ['grade' => ['Thời gian nhập điểm chưa bắt đầu cho kỳ học này']];
                }
            }

            // Xử lý các lỗi không xác định hoặc nghiêm trọng
            if ($e instanceof \Error) {
                $status = 500;
                $response['message'] = 'Lỗi máy chủ nội bộ';

                // Log error để debug
                \Log::error($e->getMessage().' '.$e->getFile().':'.$e->getLine());
            }

            return response()->json($response, $status);
        });
    }
}
