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
            $table->string('code')->unique(); // Course code (e.g., "Nhảy cao 23")
            $table->string('subject_name'); // Subject name (e.g., "Nhảy cao")
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade'); // Foreign key to terms table
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade'); // Manager of the class (User ID)
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes to preserve history
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
