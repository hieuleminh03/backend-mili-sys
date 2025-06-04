<?php

namespace App\Http\Requests;

use App\Models\StudentEquipmentReceipt;
use App\Models\YearlyEquipmentDistribution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EquipmentReceiptCreateRequest extends FormRequest
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
            'distribution_id' => 'required|exists:yearly_equipment_distributions,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'distribution_id.required' => 'ID phân phối là bắt buộc',
            'distribution_id.exists' => 'Phân phối không tồn tại',
            'student_ids.required' => 'Danh sách học viên là bắt buộc',
            'student_ids.array' => 'Danh sách học viên phải là mảng',
            'student_ids.*.exists' => 'Một hoặc nhiều học viên không tồn tại',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $distributionId = $this->input('distribution_id');
            $studentIds = $this->input('student_ids', []);
            
            if (!$distributionId || empty($studentIds)) {
                return;
            }
            
            try {
                // Validate số lượng thiết bị vs số học viên nhận
                $distribution = YearlyEquipmentDistribution::find($distributionId);
                if (!$distribution) {
                    $validator->errors()->add('distribution_id', 'Phân phối quân tư trang không tồn tại');
                    return;
                }
                
                $existingReceiptsCount = StudentEquipmentReceipt::where('distribution_id', $distributionId)->count();
                $newStudentsCount = count(array_unique($studentIds));
                $totalStudentsAfterAdd = $existingReceiptsCount + $newStudentsCount;
                
                if ($totalStudentsAfterAdd > $distribution->quantity) {
                    $maxCanAdd = $distribution->quantity - $existingReceiptsCount;
                    $validator->errors()->add(
                        'student_ids',
                        "Số lượng học viên nhận vượt quá số lượng thiết bị có sẵn. Hiện có {$existingReceiptsCount} học viên đã được phân phối, bạn chỉ có thể thêm tối đa {$maxCanAdd} học viên nữa (số lượng tối đa cho phép: {$distribution->quantity})"
                    );
                }
                
                // Kiểm tra duplicate student IDs trong request
                if (count($studentIds) !== count(array_unique($studentIds))) {
                    $validator->errors()->add(
                        'student_ids',
                        'Danh sách học viên có ID trùng lặp'
                    );
                }
                
                // Kiểm tra học viên đã có biên nhận cho đợt phân phối này chưa
                $existingStudentIds = StudentEquipmentReceipt::where('distribution_id', $distributionId)
                    ->whereIn('user_id', $studentIds)
                    ->pluck('user_id')
                    ->toArray();
                    
                if (!empty($existingStudentIds)) {
                    $validator->errors()->add(
                        'student_ids',
                        'Một số học viên đã có biên nhận cho đợt phân phối này: ' . implode(', ', $existingStudentIds)
                    );
                }
            } catch (\Exception $e) {
                // Log error để debug
                \Log::error('Equipment receipt validation error: ' . $e->getMessage());
                $validator->errors()->add('general', 'Có lỗi xảy ra trong quá trình validation');
            }
        });
    }
}
