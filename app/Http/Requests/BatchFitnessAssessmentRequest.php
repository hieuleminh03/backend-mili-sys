<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchFitnessAssessmentRequest extends FormRequest
{
    /**
     * check author
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * danh sách validation
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fitness_test_id' => 'required|integer|exists:fitness_tests,id',
            'assessment_session_id' => 'sometimes|integer|exists:fitness_assessment_sessions,id',
            'assessments' => 'required|array|min:1',
            'assessments.*.user_id' => 'required|integer|exists:users,id',
            'assessments.*.performance' => 'required|numeric',
            'assessments.*.notes' => 'sometimes|nullable|string',
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
            'fitness_test_id.required' => 'Mã bài kiểm tra thể lực là bắt buộc',
            'fitness_test_id.integer' => 'Mã bài kiểm tra thể lực phải là số nguyên',
            'fitness_test_id.exists' => 'Bài kiểm tra thể lực không tồn tại trong hệ thống',
            
            'assessment_session_id.integer' => 'Mã phiên đánh giá phải là số nguyên',
            'assessment_session_id.exists' => 'Phiên đánh giá không tồn tại trong hệ thống',
            
            'assessments.required' => 'Danh sách đánh giá là bắt buộc',
            'assessments.array' => 'Danh sách đánh giá phải là một mảng',
            'assessments.min' => 'Phải có ít nhất một bản ghi đánh giá',
            
            'assessments.*.user_id.required' => 'Mã học viên là bắt buộc',
            'assessments.*.user_id.integer' => 'Mã học viên phải là số nguyên',
            'assessments.*.user_id.exists' => 'Học viên không tồn tại trong hệ thống',
            
            'assessments.*.performance.required' => 'Kết quả thực hiện là bắt buộc',
            'assessments.*.performance.numeric' => 'Kết quả thực hiện phải là số',
            
            'assessments.*.notes.string' => 'Ghi chú phải là chuỗi',
        ];
    }
}