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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('nik')->nullable();
            $table->string('nip')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->date('joined_at')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();

            // Bank Account
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            // Salary & Allowances
            $table->unsignedBigInteger('basic_salary')->default(0);
            $table->unsignedBigInteger('fixed_allowance')->default(0);
            $table->unsignedBigInteger('daily_allowance')->default(0);
            $table->unsignedBigInteger('other_allowance')->default(0);
            $table->unsignedBigInteger('overtime_rate_per_hour')->default(0);

            // Penalties
            $table->enum('late_penalty_type', ['prorate', 'flat'])->default('prorate');
            $table->unsignedBigInteger('late_penalty_nominal')->default(0);
            $table->enum('alpha_penalty_type', ['prorate', 'flat'])->default('prorate');
            $table->unsignedBigInteger('alpha_penalty_nominal')->default(0);

            // Tax (Pajak)
            $table->string('npwp')->nullable();
            $table->string('ptkp_status')->default('TK/0');
            $table->boolean('taxable')->default(true);

            // BPJS Numbers
            $table->string('bpjs_kesehatan_no')->nullable();
            $table->string('jht_no')->nullable();
            $table->string('jp_no')->nullable();
            $table->string('jkk_no')->nullable();
            $table->string('jkm_no')->nullable();

            $table->enum('payroll_cycle', ['monthly', 'weekly'])->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
