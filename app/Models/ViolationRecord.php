<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolationRecord extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể gán hàng loạt.
     *
     * @var array
     */
    protected $fillable = [
        'student_id',
        'manager_id',
        'violation_name',
        'violation_date',
    ];

    /**
     * Các quy tắc ép kiểu cho các thuộc tính.
     *
     * @var array
     */
    protected $casts = [
        'violation_date' => 'date',
    ];

    /**
     * Lấy học viên vi phạm
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Lấy quản lý ghi nhận vi phạm
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Kiểm tra xem vi phạm còn có thể chỉnh sửa không (trong vòng 1 ngày)
     *
     * @return bool
     */
    public function isEditable()
    {
        return $this->created_at->diffInDays(now()) < 1;
    }
}
