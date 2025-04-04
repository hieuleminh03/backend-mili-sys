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
        Schema::create('student_fitness_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Học viên
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade'); // Manager đánh giá
            $table->foreignId('fitness_test_id')->constrained('fitness_tests')->onDelete('cascade'); // Bài kiểm tra
            $table->foreignId('assessment_session_id')->constrained('fitness_assessment_sessions')->onDelete('cascade'); // Tuần đánh giá
            $table->decimal('performance', 8, 2); // Kết quả thực hiện (giây, mét, lần...)
            $table->enum('rating', ['excellent', 'good', 'pass', 'fail'])->nullable(); // Xếp loại tự động dựa trên thresholds
            $table->text('notes')->nullable(); // Ghi chú
            $table->timestamps();

            // Một học viên chỉ có thể có một kết quả cho mỗi bài kiểm tra trong một tuần đánh giá
            $table->unique(['user_id', 'fitness_test_id', 'assessment_session_id']);

            // Indexes for common queries
            $table->index('manager_id');
            $table->index(['assessment_session_id', 'fitness_test_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fitness_records');
    }
};
