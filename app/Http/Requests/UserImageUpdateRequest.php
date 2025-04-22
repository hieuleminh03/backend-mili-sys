<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserImageUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow if the user is an admin or if they're updating their own image
        if (auth()->user()->isAdmin()) {
            return true;
        }

        // Check if user is trying to update their own image
        $userId = $this->route('userId') ?? $this->route('id');
        return auth()->id() === (int) $userId;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => 'required|string|max:255'
        ];
    }
}
