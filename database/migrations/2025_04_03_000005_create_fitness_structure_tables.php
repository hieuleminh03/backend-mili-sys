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
        // Create fitness_tests table
        Schema::create('fitness_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên bài kiểm tra thể lực, ví dụ "Chạy 100m"
            $table->string('unit'); // Đơn vị tính, ví dụ "giây", "mét", "lần"
            $table->boolean('higher_is_better')->default(false); // True nếu số cao hơn là tốt hơn (như mét), False nếu số thấp hơn là tốt hơn (như giây)
            $table->timestamps();
            $table->softDeletes(); // Soft delete để đảm bảo dữ liệu lịch sử

            $table->unique(['name', 'deleted_at']);
        });

        // Create fitness_test_thresholds table
        Schema::create('fitness_test_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fitness_test_id')->constrained('fitness_tests')->onDelete('cascade');
            $table->decimal('excellent_threshold', 8, 2); // Mốc cho mức "Giỏi"
            $table->decimal('good_threshold', 8, 2); // Mốc cho mức "Khá"
            $table->decimal('pass_threshold', 8, 2); // Mốc cho mức "Đạt"
            // Nếu dưới pass_threshold thì sẽ là "Không đạt"
            $table->timestamps();

            $table->unique('fitness_test_id');
        });

        // Create fitness_assessment_sessions table
        Schema::create('fitness_assessment_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Tên tuần đánh giá thể lực, ví dụ: "Tuần 1 - Tháng 4/2025"
            $table->date('week_start_date'); // Ngày bắt đầu của tuần đánh giá
            $table->date('week_end_date'); // Ngày kết thúc của tuần đánh giá
            $table->text('notes')->nullable(); // Ghi chú về tuần đánh giá
            $table->timestamps();

            $table->index('week_start_date');
            $table->index('week_end_date');
        });

        // Create student_fitness_records table
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
        Schema::dropIfExists('fitness_assessment_sessions');
        Schema::dropIfExists('fitness_test_thresholds');
        Schema::dropIfExists('fitness_tests');
    }
};