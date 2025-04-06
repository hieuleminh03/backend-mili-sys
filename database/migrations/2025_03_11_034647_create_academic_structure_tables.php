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
        // Create terms table
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date'); // Start date of the term
            $table->date('end_date'); // End date of the term (must be after start date)
            $table->date('roster_deadline'); // Deadline for roster finalization (at least 2 weeks after start_date, before end_date)
            $table->date('grade_entry_date'); // Date for grade entry (at least 2 weeks after end_date)
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes to preserve history

            $table->unique(['name', 'deleted_at'], 'terms_name_unique');
        });

        // Create courses table
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->unique(); // Mã lớp học 6 chữ số
            $table->string('subject_name'); // Tên môn học
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade'); // Foreign key đến bảng terms
            $table->integer('enroll_limit')->default(30); // Số lượng sinh viên tối đa được phép đăng ký
            $table->decimal('midterm_weight', 3, 2)->default(0.3); // Hệ số điểm giữa kỳ (giá trị giữa 0 và 1)
            $table->timestamps();
            $table->softDeletes(); // Thêm soft deletes để bảo toàn lịch sử
        });

        // Create student_courses table
        Schema::create('student_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign key đến bảng users (sinh viên)
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade'); // Foreign key đến bảng courses
            $table->decimal('midterm_grade', 5, 2)->nullable(); // Điểm giữa kỳ của sinh viên
            $table->decimal('final_grade', 5, 2)->nullable(); // Điểm cuối kỳ của sinh viên
            $table->decimal('total_grade', 5, 2)->nullable(); // Điểm tổng kết của sinh viên (tính dựa trên hệ số)
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'failed'])->default('enrolled'); // Trạng thái của sinh viên trong lớp học
            $table->text('notes')->nullable(); // Ghi chú về sinh viên trong lớp học này
            $table->timestamps();

            // Đảm bảo một sinh viên chỉ có thể đăng ký một lần trong một lớp học
            $table->unique(['user_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_courses');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('terms');
    }
};