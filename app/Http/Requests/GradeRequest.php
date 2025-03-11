<?php

namespace App\Http\Requests;

use App\Models\StudentCourse;
use Illuminate\Foundation\Http\FormRequest;

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
            'grade' => 'required|numeric|min:0|max:100',
            'status' => 'sometimes|required|in:' . implode(',', StudentCourse::getStatuses()),
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
            'grade.required' => 'A grade is required',
            'grade.numeric' => 'Grade must be a number',
            'grade.min' => 'Grade cannot be less than 0',
            'grade.max' => 'Grade cannot be more than 100',
            'status.in' => 'Invalid status value',
        ];
    }
} 