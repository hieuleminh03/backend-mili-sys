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
        Schema::create('fitness_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên bài kiểm tra thể lực, ví dụ "Chạy 100m"
            $table->string('unit'); // Đơn vị tính, ví dụ "giây", "mét", "lần"
            $table->boolean('higher_is_better')->default(false); // True nếu số cao hơn là tốt hơn (như mét), False nếu số thấp hơn là tốt hơn (như giây)
            $table->timestamps();
            $table->softDeletes(); // Soft delete để đảm bảo dữ liệu lịch sử

            $table->unique(['name', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fitness_tests');
    }
};
