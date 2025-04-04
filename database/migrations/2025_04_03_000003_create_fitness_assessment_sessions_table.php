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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fitness_assessment_sessions');
    }
};
