<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Role constants
     */
    const ROLE_STUDENT = 'student';

    const ROLE_MANAGER = 'manager';

    const ROLE_ADMIN = 'admin';

    /**
     * Available roles
     */
    const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_MANAGER,
        self::ROLE_ADMIN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is a student
     */
    public function isStudent(): bool
    {
        return $this->hasRole(self::ROLE_STUDENT);
    }

    /**
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }

    /**
     * Get the courses that the user is enrolled in.
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'student_courses')
            ->withPivot(['midterm_grade', 'final_grade', 'total_grade', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the course enrollments for the user.
     */
    public function studentCourses()
    {
        return $this->hasMany(StudentCourse::class);
    }

    /**
     * Get the courses managed by this user.
     */
    public function managedCourses()
    {
        return $this->hasMany(Course::class, 'manager_id');
    }

    /**
     * Check if the user is a student.
     *
     * @return bool
     */

    /**
     * Get the manager details for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function managerDetail()
    {
        return $this->hasOne(ManagerDetail::class);
    }

    /**
     * Get the student class details for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function studentClass()
    {
        return $this->hasOne(StudentClass::class);
    }

    /**
     * Get the class that this student belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function class()
    {
        return $this->belongsToMany(ClassRoom::class, 'student_classes')
            ->withPivot(['role', 'status', 'reason', 'note'])
            ->withTimestamps();
    }

    /**
     * Get the class that this manager manages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function managedClass()
    {
        return $this->hasOne(ClassRoom::class, 'manager_id');
    }

    /**
     * Check if user is a monitor (lớp trưởng) of any class.
     *
     * @return bool
     */
    public function isMonitor()
    {
        return $this->studentClass && $this->studentClass->role === 'monitor';
    }

    /**
     * Check if user is a vice monitor (lớp phó) of any class.
     *
     * @return bool
     */
    public function isViceMonitor()
    {
        return $this->studentClass && $this->studentClass->role === 'vice_monitor';
    }

    /**
     * Get the equipment receipts for this user
     *
     * @return HasMany
     */
    public function equipmentReceipts(): HasMany
    {
        return $this->hasMany(StudentEquipmentReceipt::class);
    }

    /**
     * Get the monthly allowances for this user
     *
     * @return HasMany
     */
    public function monthlyAllowances(): HasMany
    {
        return $this->hasMany(MonthlyAllowance::class);
    }

    /**
     * Get the student details for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function studentDetail()
    {
        return $this->hasOne(StudentDetail::class);
    }
}
