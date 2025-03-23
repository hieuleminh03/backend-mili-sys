<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ViolationCreateRequest extends FormRequest
{
    /**
     * xác định người dùng có quyền thực hiện request này không
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user() && auth()->user()->isManager();
    }

    /**
     * các quy tắc kiểm tra dữ liệu
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'student_id' => 'required|integer|exists:users,id',
            'violation_name' => 'required|string|max:100',
            'violation_date' => 'required|date|before_or_equal:today',
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
            'student_id.required' => 'Học viên là bắt buộc',
            'student_id.integer' => 'Mã học viên phải là số nguyên',
            'student_id.exists' => 'Học viên không tồn tại trong hệ thống',
            'violation_name.required' => 'Tên lỗi vi phạm là bắt buộc',
            'violation_name.string' => 'Tên lỗi vi phạm phải là chuỗi',
            'violation_name.max' => 'Tên lỗi vi phạm không được vượt quá 100 ký tự',
            'violation_date.required' => 'Ngày vi phạm là bắt buộc',
            'violation_date.date' => 'Ngày vi phạm không hợp lệ',
            'violation_date.before_or_equal' => 'Ngày vi phạm không được sau ngày hiện tại',
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