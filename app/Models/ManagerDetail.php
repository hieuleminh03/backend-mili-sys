<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagerDetail extends Model
{
    use HasFactory;

    /**
     * Enum values for role_political.
     */
    public const ROLE_POLITICAL_COMMANDER = 'COMMANDER';
    public const ROLE_POLITICAL_DEPUTY_COMMANDER = 'DEPUTY_COMMANDER';
    public const ROLE_POLITICAL_POLITICAL_OFFICER = 'POLITICAL_OFFICER';

    public static $rolePoliticalEnum = [
        self::ROLE_POLITICAL_COMMANDER,
        self::ROLE_POLITICAL_DEPUTY_COMMANDER,
        self::ROLE_POLITICAL_POLITICAL_OFFICER,
    ];

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
        'management_unit',
        'father_name',
        'father_birth_year',
        'mother_name',
        'mother_birth_year',
        'father_hometown',
        'mother_hometown',
        'permanent_address',
        'role_political',
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
