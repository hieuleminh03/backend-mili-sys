<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class GradeRequest extends FormRequest
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
            'midterm_grade' => 'sometimes|required|numeric|min:0|max:100',
            'final_grade' => 'sometimes|required|numeric|min:0|max:100',
            'notes' => 'sometimes|nullable|string',
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
            'midterm_grade.required' => 'Điểm giữa kỳ là bắt buộc',
            'midterm_grade.numeric' => 'Điểm giữa kỳ phải là số',
            'midterm_grade.min' => 'Điểm giữa kỳ không thể nhỏ hơn 0',
            'midterm_grade.max' => 'Điểm giữa kỳ không thể lớn hơn 100',
            'final_grade.required' => 'Điểm cuối kỳ là bắt buộc',
            'final_grade.numeric' => 'Điểm cuối kỳ phải là số',
            'final_grade.min' => 'Điểm cuối kỳ không thể nhỏ hơn 0',
            'final_grade.max' => 'Điểm cuối kỳ không thể lớn hơn 100',
        ];
    }

    /**
     * xử lý validation thất bại và trả về response json
     *
     * @param  Validator  $validator  validator instance
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
