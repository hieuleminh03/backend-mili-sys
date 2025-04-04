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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date'); // Start date of the term
            $table->date('end_date'); // End date of the term (must be after start date)
            $table->date('roster_deadline'); // Deadline for roster finalization (at least 2 weeks after start_date, before end_date)
            $table->date('grade_entry_date'); // Date for grade entry (at least 2 weeks after end_date)
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes to preserve history

            $table->unique(['name', 'deleted_at'], 'terms_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
