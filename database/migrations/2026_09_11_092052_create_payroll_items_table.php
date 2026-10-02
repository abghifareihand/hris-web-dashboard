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
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            
            // Basic & Allowances
            $table->unsignedBigInteger('basic_salary')->default(0);
            $table->unsignedBigInteger('fixed_allowance')->default(0);
            $table->unsignedBigInteger('daily_allowance')->default(0);
            $table->unsignedBigInteger('other_allowance')->default(0);
            $table->unsignedBigInteger('overtime_amount')->default(0);
            $table->unsignedBigInteger('bonus')->default(0);
            $table->unsignedBigInteger('reimbursement_amount')->default(0);

            // Deductions & Penalties
            $table->unsignedBigInteger('late_penalty_amount')->default(0);
            $table->unsignedBigInteger('alpha_penalty_amount')->default(0);
            $table->unsignedBigInteger('other_deductions')->default(0);
            $table->unsignedBigInteger('total_penalty')->default(0);

            // Taxes
            $table->unsignedBigInteger('pph21_amount')->default(0);
            $table->string('ptkp_status')->nullable();

            // BPJS Health
            $table->unsignedBigInteger('bpjs_kesehatan_employee')->default(0);
            $table->unsignedBigInteger('bpjs_kesehatan_company')->default(0);

            // BPJS Employment (Ketenagakerjaan)
            $table->unsignedBigInteger('jht_employee')->default(0);
            $table->unsignedBigInteger('jht_company')->default(0);
            $table->unsignedBigInteger('jp_employee')->default(0);
            $table->unsignedBigInteger('jp_company')->default(0);
            $table->unsignedBigInteger('jkk_company')->default(0);
            $table->unsignedBigInteger('jkm_company')->default(0);

            // Summary Totals
            $table->unsignedBigInteger('total_earnings')->default(0);
            $table->unsignedBigInteger('total_benefits')->default(0);
            $table->unsignedBigInteger('total_deductions')->default(0);
            $table->unsignedBigInteger('net_salary')->default(0);

            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('loan_installment_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
