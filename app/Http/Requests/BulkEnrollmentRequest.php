<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class BulkEnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorization is already handled by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'student_ids.required' => 'Danh sách sinh viên không được để trống',
            'student_ids.array' => 'Danh sách sinh viên phải là một mảng',
            'student_ids.min' => 'Danh sách sinh viên phải có ít nhất 1 sinh viên',
            'student_ids.*.required' => 'Mã sinh viên không được để trống',
            'student_ids.*.integer' => 'Mã sinh viên phải là số nguyên',
            'student_ids.*.exists' => 'Sinh viên không tồn tại trong hệ thống',
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