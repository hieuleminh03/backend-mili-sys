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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
