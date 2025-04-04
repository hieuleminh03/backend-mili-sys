<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;

    /**
     * Tên bảng trong database.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * Các thuộc tính có thể gán hàng loạt.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'manager_id',
    ];

    /**
     * Lấy manager của lớp
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Lấy danh sách học viên trong lớp
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studentClasses()
    {
        return $this->hasMany(StudentClass::class, 'class_id');
    }

    /**
     * Query cơ bản cho danh sách học viên trong lớp
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function studentsQuery()
    {
        return $this->belongsToMany(User::class, 'student_classes', 'class_id', 'user_id')
            ->withPivot(['role', 'status', 'reason', 'note'])
            ->withTimestamps();
    }

    /**
     * Lấy danh sách học viên trong lớp
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function students()
    {
        return $this->studentsQuery();
    }

    /**
     * Lấy query builder cho lớp trưởng
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function monitorQuery()
    {
        return $this->belongsToMany(User::class, 'student_classes', 'class_id', 'user_id')
            ->withPivot(['role', 'status', 'reason', 'note'])
            ->wherePivot('role', 'monitor');
    }

    /**
     * Lấy lớp trưởng của lớp
     *
     * @return \App\Models\User|null
     */
    public function monitor()
    {
        return $this->monitorQuery()->first();
    }

    /**
     * Lấy query builder cho lớp phó
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function viceMonitorsQuery()
    {
        return $this->belongsToMany(User::class, 'student_classes', 'class_id', 'user_id')
            ->withPivot(['role', 'status', 'reason', 'note'])
            ->wherePivot('role', 'vice_monitor');
    }

    /**
     * Lấy danh sách lớp phó
     *
     * @return \Illuminate\Database\Eloquent\Collection|\App\Models\User[]
     */
    public function viceMonitors()
    {
        return $this->viceMonitorsQuery()->get();
    }
}
