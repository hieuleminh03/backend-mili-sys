<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagerDetail extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể gán hàng loạt.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'full_name',
        'rank',
        'birth_year',
        'hometown',
        'phone_number',
        'is_party_member',
        'photo_url',
        'management_unit',
        'father_name',
        'father_birth_year',
        'mother_name',
        'mother_birth_year',
        'father_hometown',
        'mother_hometome',
        'permanent_address',
    ];

    /**
     * Các quy tắc ép kiểu cho các thuộc tính.
     *
     * @var array
     */
    protected $casts = [
        'birth_year' => 'integer',
        'father_birth_year' => 'integer',
        'mother_birth_year' => 'integer',
        'is_party_member' => 'boolean',
    ];

    /**
     * Lấy user của manager
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
