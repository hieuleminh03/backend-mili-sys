<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Term extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'roster_deadline',
        'grade_entry_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'roster_deadline' => 'date',
        'grade_entry_date' => 'date',
    ];

    /**
     * Get the courses associated with this term.
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Validate whether the term dates are valid according to business rules.
     *
     * @return array|true Array of errors or true if valid
     */
    public function validateDates()
    {
        $errors = [];
        
        // End date must be after start date
        if ($this->end_date <= $this->start_date) {
            $errors[] = 'End date must be after start date.';
        }
        
        // Roster deadline must be at least 2 weeks after start date
        $minRosterDeadline = Carbon::parse($this->start_date)->addWeeks(2);
        if ($this->roster_deadline < $minRosterDeadline) {
            $errors[] = 'Roster deadline must be at least 2 weeks after start date.';
        }
        
        // Roster deadline must be before end date
        if ($this->roster_deadline >= $this->end_date) {
            $errors[] = 'Roster deadline must be before end date.';
        }
        
        // Grade entry date must be at least 2 weeks after end date
        $minGradeEntryDate = Carbon::parse($this->end_date)->addWeeks(2);
        if ($this->grade_entry_date < $minGradeEntryDate) {
            $errors[] = 'Grade entry date must be at least 2 weeks after end date.';
        }
        
        return empty($errors) ? true : $errors;
    }

    /**
     * Check if this term overlaps with any other term.
     *
     * @return bool
     */
    public function hasOverlap()
    {
        $query = self::where('id', '!=', $this->id ?? 0)
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                    ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                    ->orWhere(function ($query) {
                        $query->where('start_date', '<=', $this->start_date)
                            ->where('end_date', '>=', $this->end_date);
                    });
            });
            
        return $query->exists();
    }
    
    /**
     * Validate the term name format (year-letter).
     *
     * @param string $name
     * @return bool
     */
    public static function isValidNameFormat($name)
    {
        return (bool) preg_match('/^\d{4}[A-Z]$/', $name);
    }
}
