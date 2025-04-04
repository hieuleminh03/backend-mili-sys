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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fitness_test_thresholds');
    }
};
