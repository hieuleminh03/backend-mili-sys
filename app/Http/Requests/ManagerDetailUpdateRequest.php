<?php

namespace App\Http\Requests;

use App\Models\ManagerDetail;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

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
            'birth_year' => 'nullable|integer|min:1900|max:'.date('Y'),
            'hometown' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'is_party_member' => 'nullable|boolean',
            'management_unit' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'father_birth_year' => 'nullable|integer|min:1900|max:'.date('Y'),
            'mother_name' => 'nullable|string|max:100',
            'mother_birth_year' => 'nullable|integer|min:1900|max:'.date('Y'),
            'father_hometown' => 'nullable|string|max:100',
            'mother_hometown' => 'nullable|string|max:100',
            'permanent_address' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'role_political' => ['nullable', 'string', Rule::in(ManagerDetail::$rolePoliticalEnum)],
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
            'management_unit.string' => 'Đơn vị quản lý phải là chuỗi',
            'management_unit.max' => 'Đơn vị quản lý không được vượt quá 100 ký tự',
            'father_name.string' => 'Họ tên bố phải là chuỗi',
            'father_name.max' => 'Họ tên bố không được vượt quá 100 ký tự',
            'father_birth_year.integer' => 'Năm sinh của bố phải là số nguyên',
            'father_birth_year.min' => 'Năm sinh của bố không hợp lệ',
            'father_birth_year.max' => 'Năm sinh của bố không được lớn hơn năm hiện tại',
            'mother_name.string' => 'Họ tên mẹ phải là chuỗi',
            'mother_name.max' => 'Họ tên mẹ không được vượt quá 100 ký tự',
            'mother_birth_year.integer' => 'Năm sinh của mẹ phải là số nguyên',
            'mother_birth_year.min' => 'Năm sinh của mẹ không hợp lệ',
            'mother_birth_year.max' => 'Năm sinh của mẹ không được lớn hơn năm hiện tại',
            'father_hometown.string' => 'Quê quán bố phải là chuỗi',
            'father_hometown.max' => 'Quê quán bố không được vượt quá 100 ký tự',
            'mother_hometown.string' => 'Quê quán mẹ phải là chuỗi',
            'mother_hometown.max' => 'Quê quán mẹ không được vượt quá 100 ký tự',
            'permanent_address.string' => 'Địa chỉ thường trú phải là chuỗi',
            'permanent_address.max' => 'Địa chỉ thường trú không được vượt quá 255 ký tự',
            'image.string' => 'Ảnh đại diện phải là chuỗi',
            'image.max' => 'Ảnh đại diện không được vượt quá 255 ký tự',
            'role_political.string' => 'Chức vụ phải là chuỗi.',
            'role_political.in' => 'Chức vụ không hợp lệ. Các giá trị được chấp nhận là: '.implode(', ', ManagerDetail::$rolePoliticalEnum).'.',
        ];
    }

    /**
     * xử lý validation thất bại và trả về response json
     *
     * @param  Validator  $validator  validator instance
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
