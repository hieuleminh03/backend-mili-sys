<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FitnessAssessmentRequest extends FormRequest
{
    /**
     * check author
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * check validation
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'fitness_test_id' => 'required|integer|exists:fitness_tests,id',
            'assessment_session_id' => 'sometimes|integer|exists:fitness_assessment_sessions,id',
            'performance' => 'required|numeric',
            'notes' => 'sometimes|nullable|string',
        ];
    }

    /**
     * error messages cho validation
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Mã học viên là bắt buộc',
            'user_id.integer' => 'Mã học viên phải là số nguyên',
            'user_id.exists' => 'Học viên không tồn tại trong hệ thống',

            'fitness_test_id.required' => 'Mã bài kiểm tra thể lực là bắt buộc',
            'fitness_test_id.integer' => 'Mã bài kiểm tra thể lực phải là số nguyên',
            'fitness_test_id.exists' => 'Bài kiểm tra thể lực không tồn tại trong hệ thống',

            'assessment_session_id.integer' => 'Mã phiên đánh giá phải là số nguyên',
            'assessment_session_id.exists' => 'Phiên đánh giá không tồn tại trong hệ thống',

            'performance.required' => 'Kết quả thực hiện là bắt buộc',
            'performance.numeric' => 'Kết quả thực hiện phải là số',

            'notes.string' => 'Ghi chú phải là chuỗi',
        ];
    }
}
