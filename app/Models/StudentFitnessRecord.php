<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFitnessRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'manager_id',
        'fitness_test_id',
        'assessment_session_id',
        'performance',
        'rating',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'performance' => 'decimal:2',
    ];

    /**
     * Get the student (user) that owns this record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the manager that created this record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the fitness test for this record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fitnessTest(): BelongsTo
    {
        return $this->belongsTo(FitnessTest::class, 'fitness_test_id');
    }

    /**
     * Get the assessment session for this record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assessmentSession(): BelongsTo
    {
        return $this->belongsTo(FitnessAssessmentSession::class, 'assessment_session_id');
    }

    /**
     * Tự động tính toán rating dựa trên performance và thresholds
     * Gọi trước khi lưu record
     *
     * @return void
     */
    public function calculateRating(): void
    {
        if ($this->fitnessTest && $this->fitnessTest->thresholds) {
            $this->rating = $this->fitnessTest->determineRating($this->performance);
        }
    }
}