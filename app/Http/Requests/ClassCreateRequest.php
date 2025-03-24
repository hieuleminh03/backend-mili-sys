<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ClassCreateRequest extends FormRequest
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
            'name' => 'required|string|max:100|unique:classes,name',
            'manager_id' => 'nullable|integer|exists:users,id,role,manager'
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
            'name.required' => 'Tên lớp là bắt buộc',
            'name.string' => 'Tên lớp phải là chuỗi',
            'name.max' => 'Tên lớp không được vượt quá 100 ký tự',
            'name.unique' => 'Tên lớp đã tồn tại',
            'manager_id.integer' => 'ID quản lý phải là số nguyên',
            'manager_id.exists' => 'Quản lý không tồn tại hoặc không phải là manager'
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