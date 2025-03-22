<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class BulkGradeUpdateRequest extends FormRequest
{
    /**
     * xác định người dùng có quyền thực hiện request này không
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * các quy tắc kiểm tra dữ liệu
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'grades' => 'required|array|min:1',
            'grades.*.user_id' => 'required|integer|exists:users,id',
            'grades.*.midterm_grade' => 'nullable|numeric|min:0|max:10',
            'grades.*.final_grade' => 'nullable|numeric|min:0|max:10',
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
            'grades.required' => 'Danh sách điểm học viên là bắt buộc',
            'grades.array' => 'Dữ liệu điểm phải là một mảng',
            'grades.min' => 'Phải có ít nhất một học viên',
            'grades.*.user_id.required' => 'Mã học viên là bắt buộc',
            'grades.*.user_id.integer' => 'Mã học viên phải là số nguyên',
            'grades.*.user_id.exists' => 'Học viên không tồn tại trong hệ thống',
            'grades.*.midterm_grade.numeric' => 'Điểm giữa kỳ phải là số',
            'grades.*.midterm_grade.min' => 'Điểm giữa kỳ không được nhỏ hơn 0',
            'grades.*.midterm_grade.max' => 'Điểm giữa kỳ không được lớn hơn 10',
            'grades.*.final_grade.numeric' => 'Điểm cuối kỳ phải là số',
            'grades.*.final_grade.min' => 'Điểm cuối kỳ không được nhỏ hơn 0',
            'grades.*.final_grade.max' => 'Điểm cuối kỳ không được lớn hơn 10',
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