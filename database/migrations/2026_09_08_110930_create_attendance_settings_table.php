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
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->integer('late_tolerance_minutes')->default(10);
            $table->integer('early_clock_in_minutes')->default(60);
            $table->enum('camera_mode', ['front', 'back', 'both'])->default('both');
            $table->string('status_very_good')->default('Sangat Baik');
            $table->string('status_good')->default('Baik');
            $table->string('status_fair')->default('Cukup');
            $table->string('status_poor')->default('Kurang');
            $table->timestamps();
            
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
