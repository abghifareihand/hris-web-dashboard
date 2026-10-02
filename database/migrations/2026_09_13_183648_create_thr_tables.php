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
        Schema::create('company_thr_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            $table->boolean('is_active')->default(true);
            $table->integer('min_months_tenure')->default(1);
            $table->integer('full_thr_months_tenure')->default(12);
            $table->boolean('include_fixed_allowance')->default(false);
            
            $table->timestamps();
        });

        Schema::create('thrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->integer('year');
            $table->string('holiday_name');
            $table->date('payment_date');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['process', 'paid', 'cancelled'])->default('process');
            $table->integer('total_employees')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->timestamps();
        });

        Schema::create('thr_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thr_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            
            $table->integer('tenure_months');
            $table->unsignedBigInteger('basis_amount'); // Base amount (salary + allowance if applicable)
            $table->decimal('prorate_multiplier', 5, 2); // e.g., 0.5 for 6 months, 1.0 for 12+ months
            $table->unsignedBigInteger('thr_amount'); // Final calculated amount before tax
            $table->unsignedBigInteger('tax_amount')->default(0); // PPh21 THR (default 0 for MVP)
            $table->unsignedBigInteger('net_amount'); // Final amount to be paid
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thr_items');
        Schema::dropIfExists('thrs');
        Schema::dropIfExists('company_thr_settings');
    }
};
