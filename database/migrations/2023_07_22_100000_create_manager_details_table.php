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
        Schema::create('manager_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('full_name', 100)->nullable();
            $table->string('rank', 50)->nullable();
            $table->year('birth_year')->nullable();
            $table->string('hometown', 100)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->boolean('is_party_member')->default(false);
            $table->string('management_unit', 100)->nullable();
            $table->string('father_name', 100)->nullable();
            $table->year('father_birth_year')->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->year('mother_birth_year')->nullable();
            $table->string('father_hometown', 100)->nullable();
            $table->string('mother_hometown', 100)->nullable();
            $table->string('permanent_address', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_details');
    }
};
