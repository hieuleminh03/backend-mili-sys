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
        Schema::create('yearly_equipment_distributions', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->foreignId('equipment_type_id')->constrained('military_equipment_types');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yearly_equipment_distributions');
    }
};