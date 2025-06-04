<?php

namespace App\Http\Requests;

use App\Models\StudentEquipmentReceipt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DistributionUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => 'required|integer|min:2000|max:2100',
            'equipment_type_id' => 'required|exists:military_equipment_types,id',
            'quantity' => 'required|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'year.required' => 'Năm là bắt buộc',
            'year.integer' => 'Năm phải là số nguyên',
            'year.min' => 'Năm phải từ 2000 trở lên',
            'year.max' => 'Năm không được quá 2100',
            'equipment_type_id.required' => 'Loại quân tư trang là bắt buộc',
            'equipment_type_id.exists' => 'Loại quân tư trang không tồn tại',
            'quantity.required' => 'Số lượng là bắt buộc',
            'quantity.integer' => 'Số lượng phải là số nguyên',
            'quantity.min' => 'Số lượng phải lớn hơn 0',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $distributionId = $this->route('id');
            $newQuantity = $this->input('quantity');
            
            // Kiểm tra số lượng thiết bị không được nhỏ hơn số học viên đã được phân phối
            $existingReceiptsCount = StudentEquipmentReceipt::where('distribution_id', $distributionId)->count();
            
            if ($newQuantity < $existingReceiptsCount) {
                $validator->errors()->add(
                    'quantity',
                    "Số lượng thiết bị không thể nhỏ hơn số học viên đã được phân phối ({$existingReceiptsCount} học viên). Số lượng phải ít nhất là {$existingReceiptsCount}"
                );
            }
        });
    }
}
