<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create military_equipment_types table
        Schema::create('military_equipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create yearly_equipment_distributions table
        Schema::create('yearly_equipment_distributions', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->foreignId('equipment_type_id')->constrained('military_equipment_types');
            $table->integer('quantity');
            $table->timestamps();
        });

        // Create student_equipment_receipts table
        Schema::create('student_equipment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('distribution_id')->constrained('yearly_equipment_distributions');
            $table->boolean('received')->default(false);
            $table->timestamp('received_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_equipment_receipts');
        Schema::dropIfExists('yearly_equipment_distributions');
        Schema::dropIfExists('military_equipment_types');
    }
};