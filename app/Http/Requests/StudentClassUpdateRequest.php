<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class StudentClassUpdateRequest extends FormRequest
{
    /**
     * xác định người dùng có quyền thực hiện request này không
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isManager());
    }

    /**
     * các quy tắc kiểm tra dữ liệu
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'role' => 'nullable|string|in:monitor,vice_monitor,student',
            'status' => 'nullable|string|in:active,suspended',
            'reason' => 'nullable|string|required_if:status,suspended',
            'note' => 'nullable|string'
        ];
    }

    /**
     * thông báo lỗi tùy chỉnh
     *
     * @return array
     */
    public function messages()
    {
        return [
            'role.string' => 'Vai trò phải là chuỗi',
            'role.in' => 'Vai trò không hợp lệ',
            'status.string' => 'Trạng thái phải là chuỗi',
            'status.in' => 'Trạng thái không hợp lệ',
            'reason.string' => 'Lý do phải là chuỗi',
            'reason.required_if' => 'Lý do là bắt buộc khi trạng thái là tạm hoãn',
            'note.string' => 'Ghi chú phải là chuỗi'
        ];
    }

    /**
     * xử lý validation thất bại và trả về response json
     *
     * @param Validator $validator validator instance
     * @return void
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors()
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
} 