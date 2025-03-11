<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'code' => 'required|string|unique:courses,code',
            'subject_name' => 'required|string',
            'term_id' => 'required|exists:terms,id',
            'manager_id' => 'required|exists:users,id'
        ];

        // If this is an update request, exclude the current course from unique validation
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $courseId = $this->route('id');
            $rules['code'] = "sometimes|required|string|unique:courses,code,{$courseId}";
            $rules['subject_name'] = 'sometimes|required|string';
            $rules['term_id'] = 'sometimes|required|exists:terms,id';
            $rules['manager_id'] = 'sometimes|required|exists:users,id';
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
            'code.unique' => 'This course code is already in use',
            'term_id.exists' => 'The selected term does not exist',
            'manager_id.exists' => 'The selected manager does not exist',
        ];
    }
} 