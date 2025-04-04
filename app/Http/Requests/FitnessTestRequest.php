<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FitnessTestRequest extends FormRequest
{
    /**
     * Check authorization for the request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware.
    }

    /**
     * Get validation rules for the request.
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

        // If posting or if thresholds exist, require threshold fields
        if ($this->isMethod('post') || $this->has('excellent_threshold')) {
            $rules['excellent_threshold'] = 'required|numeric';
        }

        if ($this->isMethod('post') || $this->has('good_threshold')) {
            $rules['good_threshold'] = 'required|numeric';
        }

        if ($this->isMethod('post') || $this->has('pass_threshold')) {
            $rules['pass_threshold'] = 'required|numeric';
        }

        return $rules;
    }

    /**
     * Configure additional custom validation after the default validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateThresholdOrder($validator);
        });
    }

    /**
     * Validate whether the provided thresholds have the correct order.
     */
    protected function validateThresholdOrder(Validator $validator): void
    {
        // Only validate if all thresholds are present.
        if (! $this->has('excellent_threshold') || ! $this->has('good_threshold') || ! $this->has('pass_threshold')) {
            return;
        }

        $excellent = (float) $this->input('excellent_threshold');
        $good = (float) $this->input('good_threshold');
        $pass = (float) $this->input('pass_threshold');
        $higherIsBetter = $this->input('higher_is_better', false);

        if ($higherIsBetter) {
            // When higher values are better: excellent > good > pass.
            if ($excellent <= $good) {
                $validator->errors()->add('excellent_threshold', 'Ngưỡng Giỏi phải cao hơn ngưỡng Khá');
            }
            if ($good <= $pass) {
                $validator->errors()->add('good_threshold', 'Ngưỡng Khá phải cao hơn ngưỡng Đạt');
            }
        } else {
            // When lower values are better: excellent < good < pass.
            if ($excellent >= $good) {
                $validator->errors()->add('excellent_threshold', 'Ngưỡng Giỏi phải thấp hơn ngưỡng Khá');
            }
            if ($good >= $pass) {
                $validator->errors()->add('good_threshold', 'Ngưỡng Khá phải thấp hơn ngưỡng Đạt');
            }
        }
    }

    /**
     * Get custom error messages for validation rules.
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
