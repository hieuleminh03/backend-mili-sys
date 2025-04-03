<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FitnessTestRequest extends FormRequest
{
    /**
     * Check author
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Check validation
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'higher_is_better' => 'sometimes|boolean',
        ];
        
        // nếu là post hoặc là put/patch và có field excellent_threshold
        if ($this->isMethod('post') || $this->has('excellent_threshold')) {
            $rules['excellent_threshold'] = 'required|numeric';
        }
        
        // nếu là post hoặc là put/patch và có field good_threshold
        if ($this->isMethod('post') || $this->has('good_threshold')) {
            $rules['good_threshold'] = 'required|numeric';
        }
        
        // nếu là post hoặc là put/patch và có field pass_threshold
        if ($this->isMethod('post') || $this->has('pass_threshold')) {
            $rules['pass_threshold'] = 'required|numeric';
        }
        
        return $rules;
    }

    /**
     * Error messages cho validation
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên bài kiểm tra thể lực là bắt buộc',
            'name.string' => 'Tên bài kiểm tra thể lực phải là chuỗi',
            'name.max' => 'Tên bài kiểm tra thể lực không được vượt quá 255 ký tự',
            'unit.required' => 'Đơn vị tính là bắt buộc',
            'unit.string' => 'Đơn vị tính phải là chuỗi',
            'unit.max' => 'Đơn vị tính không được vượt quá 50 ký tự',
            'higher_is_better.boolean' => 'Trường higher_is_better phải là giá trị boolean',
            'excellent_threshold.required' => 'Ngưỡng mức Giỏi là bắt buộc',
            'excellent_threshold.numeric' => 'Ngưỡng mức Giỏi phải là số',
            'good_threshold.required' => 'Ngưỡng mức Khá là bắt buộc',
            'good_threshold.numeric' => 'Ngưỡng mức Khá phải là số',
            'pass_threshold.required' => 'Ngưỡng mức Đạt là bắt buộc',
            'pass_threshold.numeric' => 'Ngưỡng mức Đạt phải là số',
        ];
    }
}