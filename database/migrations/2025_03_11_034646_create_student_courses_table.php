<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign key to users table (student)
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade'); // Foreign key to courses table
            $table->decimal('grade', 5, 2)->nullable(); // Student's grade for the course
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'failed'])->default('enrolled'); // Status of the student in the course
            $table->text('notes')->nullable(); // Optional notes about the student in this course
            $table->timestamps();
            
            // Make sure a student can only be enrolled once in a course
            $table->unique(['user_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_courses');
    }
};
