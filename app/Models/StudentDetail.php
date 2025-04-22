<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDetail extends Model
{
    use HasFactory;

    /**
     * Political status constants
     */
    const POLITICAL_STATUS_PARTY_MEMBER = 'party_member'; // Đảng viên
    const POLITICAL_STATUS_YOUTH_UNION_MEMBER = 'youth_union_member'; // Đoàn viên
    const POLITICAL_STATUS_NONE = 'none';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'rank',
        'place_of_origin',
        'working_unit',
        'year_of_study',
        'political_status',
        'phone_number',
        'permanent_residence',
        // Father
        'father_name',
        'father_birth_year',
        'father_phone_number',
        'father_place_of_origin',
        'father_occupation',
        // Mother
        'mother_name',
        'mother_birth_year',
        'mother_phone_number',
        'mother_place_of_origin',
        'mother_occupation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'year_of_study' => 'integer',
        'father_birth_year' => 'integer',
        'mother_birth_year' => 'integer',
    ];

    /**
     * Get the user that owns the student detail.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
