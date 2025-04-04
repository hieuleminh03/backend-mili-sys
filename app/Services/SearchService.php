<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * tìm kiếm sinh viên theo tên hoặc email
     * nếu query rỗng thì trả về tất cả sinh viên
     *
     * @param  string|null  $query  từ khóa tìm kiếm
     * @return Collection danh sách sinh viên phù hợp
     */
    public function searchStudents(?string $query = null): Collection
    {
        // Nếu query null hoặc rỗng, trả về tất cả sinh viên
        if ($query === null || trim($query) === '') {
            return User::where('role', User::ROLE_STUDENT)
                ->select('id', 'name', 'email')
                ->get();
        }

        // Sanitize input: trim và lowercase
        $sanitizedQuery = trim(strtolower($query));

        // Tìm kiếm sinh viên theo tên hoặc email
        return User::where('role', User::ROLE_STUDENT)
            ->where(function ($queryBuilder) use ($sanitizedQuery) {
                $queryBuilder->whereRaw('LOWER(name) LIKE ?', ['%'.$sanitizedQuery.'%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$sanitizedQuery.'%']);
            })
            ->select('id', 'name', 'email')
            ->get();
    }
}
