<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'name.regex' => 'Term name must follow the format YYYY[A-Z] (e.g., 2024A)',
            'end_date.after' => 'End date must be after start date',
            'roster_deadline.after_or_equal' => 'Roster deadline must be on or after start date',
            'roster_deadline.before' => 'Roster deadline must be before end date',
            'grade_entry_date.after' => 'Grade entry date must be after end date',
        ];
    }
} 