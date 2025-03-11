<?php

namespace App\Services;

use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TermService
{
    /**
     * Get all terms ordered by start date descending.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllTerms(): Collection
    {
        return Term::orderBy('start_date', 'desc')->get();
    }

    /**
     * Get a specific term with its courses.
     *
     * @param int $id
     * @return Term
     */
    public function getTerm(int $id): Term
    {
        return Term::with('courses')->findOrFail($id);
    }

    /**
     * Create a new term with validation.
     *
     * @param array $data
     * @return Term
     * @throws \Exception
     */
    public function createTerm(array $data): Term
    {
        // Start a database transaction
        return DB::transaction(function () use ($data) {
            $term = new Term($data);
            
            // Validate term name format
            if (!Term::isValidNameFormat($term->name)) {
                throw new \Exception('Term name must follow the format YYYY[A-Z] (e.g., 2024A)');
            }
            
            // Additional date validations
            $dateValidation = $this->validateDates($term);
            if ($dateValidation !== true) {
                throw new \Exception('Date validation failed: ' . implode(', ', $dateValidation));
            }
            
            // Check for overlapping terms
            if ($this->hasTermOverlap($term)) {
                throw new \Exception('Term dates overlap with an existing term');
            }
            
            $term->save();
            return $term;
        });
    }

    /**
     * Update an existing term with validation.
     *
     * @param int $id
     * @param array $data
     * @return Term
     * @throws \Exception
     */
    public function updateTerm(int $id, array $data): Term
    {
        return DB::transaction(function () use ($id, $data) {
            $term = Term::findOrFail($id);
            $term->fill($data);
            
            // Validate term name format if it changed
            if ($term->isDirty('name') && !Term::isValidNameFormat($term->name)) {
                throw new \Exception('Term name must follow the format YYYY[A-Z] (e.g., 2024A)');
            }
            
            // Additional date validations
            $dateValidation = $this->validateDates($term);
            if ($dateValidation !== true) {
                throw new \Exception('Date validation failed: ' . implode(', ', $dateValidation));
            }
            
            // Check for overlapping terms
            if ($this->hasTermOverlap($term)) {
                throw new \Exception('Term dates overlap with an existing term');
            }
            
            $term->save();
            return $term;
        });
    }

    /**
     * Delete a term if it has no associated courses.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteTerm(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $term = Term::findOrFail($id);
            
            // Check if there are any courses associated with this term
            if ($term->courses()->count() > 0) {
                throw new \Exception('Cannot delete term because it has associated courses');
            }
            
            return $term->delete();
        });
    }

    /**
     * Validate whether the term dates are valid according to business rules.
     *
     * @param Term $term
     * @return array|true Array of errors or true if valid
     */
    protected function validateDates(Term $term)
    {
        $errors = [];
        
        // End date must be after start date
        if ($term->end_date <= $term->start_date) {
            $errors[] = 'End date must be after start date';
        }
        
        // Roster deadline must be at least 2 weeks after start date
        $minRosterDeadline = Carbon::parse($term->start_date)->addWeeks(2);
        if ($term->roster_deadline < $minRosterDeadline) {
            $errors[] = 'Roster deadline must be at least 2 weeks after start date';
        }
        
        // Roster deadline must be before end date
        if ($term->roster_deadline >= $term->end_date) {
            $errors[] = 'Roster deadline must be before end date';
        }
        
        // Grade entry date must be at least 2 weeks after end date
        $minGradeEntryDate = Carbon::parse($term->end_date)->addWeeks(2);
        if ($term->grade_entry_date < $minGradeEntryDate) {
            $errors[] = 'Grade entry date must be at least 2 weeks after end date';
        }
        
        return empty($errors) ? true : $errors;
    }

    /**
     * Check if a term overlaps with any other term.
     *
     * @param Term $term
     * @return bool
     */
    protected function hasTermOverlap(Term $term): bool
    {
        $query = Term::where('id', '!=', $term->id ?? 0)
            ->where(function ($query) use ($term) {
                $query->whereBetween('start_date', [$term->start_date, $term->end_date])
                    ->orWhereBetween('end_date', [$term->start_date, $term->end_date])
                    ->orWhere(function ($query) use ($term) {
                        $query->where('start_date', '<=', $term->start_date)
                            ->where('end_date', '>=', $term->end_date);
                    });
            });
            
        return $query->exists();
    }
} 