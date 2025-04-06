<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Create classes table
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('users')
                ->onDelete('set null');
        });

        // Create student_classes table
        Schema::create('student_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('class_id');
            $table->enum('role', ['monitor', 'vice_monitor', 'student'])->default('student');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->text('reason')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('class_id')->references('id')->on('classes')
                ->onDelete('cascade');

            // Đảm bảo một học viên chỉ thuộc về duy nhất một lớp
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_classes');
        Schema::dropIfExists('classes');
    }
};