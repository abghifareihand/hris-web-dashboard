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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            
            // Reference to shift, nullable if no shift assigned (flexible)
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            
            // Clock In Data
            $table->dateTime('clock_in_time')->nullable();
            $table->decimal('clock_in_latitude', 10, 8)->nullable();
            $table->decimal('clock_in_longitude', 11, 8)->nullable();
            $table->string('clock_in_photo')->nullable();
            
            // Clock Out Data
            $table->dateTime('clock_out_time')->nullable();
            $table->decimal('clock_out_latitude', 10, 8)->nullable();
            $table->decimal('clock_out_longitude', 11, 8)->nullable();
            $table->string('clock_out_photo')->nullable();
            
            // Calculations
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leaving_minutes')->default(0);
            $table->integer('total_work_minutes')->default(0);
            $table->integer('total_violation_minutes')->default(0);
            
            // Status/Notes
            $table->string('attendance_status')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Prevent duplicate attendance for same employee per date
            $table->unique(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
