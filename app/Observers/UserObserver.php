<?php

namespace App\Observers;

use App\Models\ManagerDetail;
use App\Models\User;

class UserObserver
{
    /**
     * Xử lý sự kiện sau khi một user được tạo.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        // Nếu user là manager, tạo một bản ghi manager_detail tương ứng
        if ($user->isManager()) {
            ManagerDetail::create([
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Xử lý sự kiện sau khi một user được cập nhật.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        // Nếu role được cập nhật thành manager và chưa có bản ghi manager_detail
        if ($user->isManager() && !$user->managerDetail) {
            ManagerDetail::create([
                'user_id' => $user->id,
            ]);
        }
    }
} 