<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentClass extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể gán hàng loạt.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'class_id',
        'role',
        'status',
        'reason',
        'note'
    ];

    /**
     * Danh sách các vai trò có thể có.
     *
     * @var array
     */
    const ROLES = [
        'monitor' => 'Lớp trưởng',
        'vice_monitor' => 'Lớp phó',
        'student' => 'Học viên'
    ];

    /**
     * Danh sách các trạng thái có thể có.
     *
     * @var array
     */
    const STATUSES = [
        'active' => 'Đang học',
        'suspended' => 'Tạm hoãn'
    ];

    /**
     * Lấy thông tin học viên
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lấy thông tin lớp học
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
} 