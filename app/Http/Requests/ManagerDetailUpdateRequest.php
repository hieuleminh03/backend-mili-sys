<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ManagerDetailUpdateRequest extends FormRequest
{
    /**
     * xác định người dùng có quyền thực hiện request này không
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user() && auth()->user()->isAdmin();
    }

    /**
     * các quy tắc kiểm tra dữ liệu
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'full_name' => 'nullable|string|max:100',
            'rank' => 'nullable|string|max:50',
            'birth_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'hometown' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'is_party_member' => 'nullable|boolean',
            'photo_url' => 'nullable|string|max:255',
            'management_unit' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'parent_hometown' => 'nullable|string|max:100',
            'permanent_address' => 'nullable|string|max:255',
        ];
    }

    /**
     * thông báo lỗi tùy chỉnh
     *
     * @return array
     */
    public function messages()
    {
        return [
            'full_name.string' => 'Họ tên phải là chuỗi',
            'full_name.max' => 'Họ tên không được vượt quá 100 ký tự',
            'rank.string' => 'Cấp bậc phải là chuỗi',
            'rank.max' => 'Cấp bậc không được vượt quá 50 ký tự',
            'birth_year.integer' => 'Năm sinh phải là số nguyên',
            'birth_year.min' => 'Năm sinh không hợp lệ',
            'birth_year.max' => 'Năm sinh không được lớn hơn năm hiện tại',
            'hometown.string' => 'Quê quán phải là chuỗi',
            'hometown.max' => 'Quê quán không được vượt quá 100 ký tự',
            'phone_number.string' => 'Số điện thoại phải là chuỗi',
            'phone_number.max' => 'Số điện thoại không được vượt quá 20 ký tự',
            'is_party_member.boolean' => 'Trường Đảng viên phải là giá trị boolean',
            'photo_url.string' => 'URL ảnh phải là chuỗi',
            'photo_url.max' => 'URL ảnh không được vượt quá 255 ký tự',
            'management_unit.string' => 'Đơn vị quản lý phải là chuỗi',
            'management_unit.max' => 'Đơn vị quản lý không được vượt quá 100 ký tự',
            'father_name.string' => 'Họ tên bố phải là chuỗi',
            'father_name.max' => 'Họ tên bố không được vượt quá 100 ký tự',
            'mother_name.string' => 'Họ tên mẹ phải là chuỗi',
            'mother_name.max' => 'Họ tên mẹ không được vượt quá 100 ký tự',
            'parent_hometown.string' => 'Quê quán bố mẹ phải là chuỗi',
            'parent_hometown.max' => 'Quê quán bố mẹ không được vượt quá 100 ký tự',
            'permanent_address.string' => 'Địa chỉ thường trú phải là chuỗi',
            'permanent_address.max' => 'Địa chỉ thường trú không được vượt quá 255 ký tự',
        ];
    }

    /**
     * xử lý validation thất bại và trả về response json
     *
     * @param Validator $validator validator instance
     * @return void
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors()
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
} 