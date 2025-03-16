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
    }
};
