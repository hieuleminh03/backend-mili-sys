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
        Schema::create('student_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Thông tin cá nhân
            $table->date('date_of_birth')->nullable();
            $table->string('rank')->nullable(); // Cấp bậc
            $table->string('place_of_origin')->nullable(); // Quê quán
            $table->string('working_unit')->nullable(); // Đơn vị công tác
            $table->integer('year_of_study')->nullable(); // Năm thứ mấy
            $table->enum('political_status', ['party_member', 'youth_union_member', 'none'])->default('none');
            $table->string('phone_number')->nullable();
            $table->text('permanent_residence')->nullable(); // Hộ khẩu thường trú
            
            // Thông tin gia đình (bố)
            $table->string('father_name')->nullable();
            $table->integer('father_birth_year')->nullable();
            $table->string('father_phone_number')->nullable();
            $table->string('father_place_of_origin')->nullable();
            $table->string('father_occupation')->nullable();

            // Thông tin gia đình (mẹ)
            $table->string('mother_name')->nullable();
            $table->integer('mother_birth_year')->nullable();
            $table->string('mother_phone_number')->nullable();
            $table->string('mother_place_of_origin')->nullable();
            $table->string('mother_occupation')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_details');
    }
};
