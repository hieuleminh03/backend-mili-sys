<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class TermRequest extends FormRequest
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
            'name' => 'required|string|regex:/^\d{4}[A-Z]$/|unique:terms,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'roster_deadline' => 'required|date|after_or_equal:start_date|before:end_date',
            'grade_entry_date' => 'required|date|after:end_date',
        ];

        // If this is an update request, exclude the current term from unique validation
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $termId = $this->route('id');
            $rules['name'] = "sometimes|required|string|regex:/^\d{4}[A-Z]$/|unique:terms,name,{$termId}";
            $rules['start_date'] = 'sometimes|required|date';
            $rules['end_date'] = 'sometimes|required|date|after:start_date';
            $rules['roster_deadline'] = 'sometimes|required|date|after_or_equal:start_date|before:end_date';
            $rules['grade_entry_date'] = 'sometimes|required|date|after:end_date';
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
            'name.required' => 'Tên học kỳ không được để trống',
            'name.regex' => 'Tên học kỳ phải theo định dạng YYYY[A-Z] (ví dụ: 2024A)',
            'name.unique' => 'Tên học kỳ đã tồn tại trong hệ thống',
            'start_date.required' => 'Ngày bắt đầu không được để trống',
            'start_date.date' => 'Ngày bắt đầu phải là một ngày hợp lệ',
            'end_date.required' => 'Ngày kết thúc không được để trống',
            'end_date.date' => 'Ngày kết thúc phải là một ngày hợp lệ',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
            'roster_deadline.required' => 'Hạn chót đăng ký không được để trống',
            'roster_deadline.date' => 'Hạn chót đăng ký phải là một ngày hợp lệ',
            'roster_deadline.after_or_equal' => 'Hạn chót đăng ký phải từ ngày bắt đầu trở đi',
            'roster_deadline.before' => 'Hạn chót đăng ký phải trước ngày kết thúc',
            'grade_entry_date.required' => 'Ngày nhập điểm không được để trống',
            'grade_entry_date.date' => 'Ngày nhập điểm phải là một ngày hợp lệ',
            'grade_entry_date.after' => 'Ngày nhập điểm phải sau ngày kết thúc',
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