<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CourseRequest extends FormRequest
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
        $rules = [
            'subject_name' => 'required|string',
            'term_id' => 'required|integer',
            'enroll_limit' => 'required|integer|min:1',
            'midterm_weight' => 'required|numeric|min:0|max:1',
        ];

        // For create requests, the code is auto-generated
        if ($this->isMethod('POST')) {
            $rules['code'] = 'sometimes';
        }

        // If this is an update request, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $courseId = $this->route('id');
            $rules['subject_name'] = 'sometimes|required|string';
            // Ban việc thay đổi term_id khi cập nhật
            $rules['term_id'] = 'prohibited';
            $rules['enroll_limit'] = 'sometimes|required|integer|min:1';
            $rules['midterm_weight'] = 'sometimes|required|numeric|min:0|max:1';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'subject_name.required' => 'Tên môn học là bắt buộc',
            'term_id.exists' => 'Kỳ học đã chọn không tồn tại',
            'term_id.required' => 'Kỳ học là bắt buộc',
            'term_id.integer' => 'Mã kỳ học phải là số nguyên',
            'term_id.prohibited' => 'Không thể thay đổi kỳ học sau khi đã tạo lớp',
            'enroll_limit.required' => 'Giới hạn đăng ký là bắt buộc',
            'enroll_limit.integer' => 'Giới hạn đăng ký phải là số nguyên',
            'enroll_limit.min' => 'Giới hạn đăng ký phải ít nhất là 1',
            'midterm_weight.required' => 'Hệ số điểm giữa kỳ là bắt buộc',
            'midterm_weight.numeric' => 'Hệ số điểm giữa kỳ phải là số',
            'midterm_weight.min' => 'Hệ số điểm giữa kỳ không thể nhỏ hơn 0',
            'midterm_weight.max' => 'Hệ số điểm giữa kỳ không thể lớn hơn 1',
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
