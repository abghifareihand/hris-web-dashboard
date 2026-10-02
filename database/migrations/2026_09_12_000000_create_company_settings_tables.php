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
        Schema::create('company_bpjs_kesehatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            $table->boolean('is_active')->default(true);
            $table->decimal('company_percent', 5, 2)->default(4.00);
            $table->decimal('employee_percent', 5, 2)->default(1.00);
            
            $table->unsignedBigInteger('minimum_wage')->nullable()->default(null);
            $table->unsignedBigInteger('maximum_wage')->nullable()->default(12000000);
            
            $table->integer('effective_year')->default(2026);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        Schema::create('company_bpjs_ketenagakerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // JHT
            $table->boolean('jht_active')->default(true);
            $table->decimal('jht_company_percent', 5, 2)->default(3.70);
            $table->decimal('jht_employee_percent', 5, 2)->default(2.00);

            // JKK
            $table->boolean('jkk_active')->default(true);
            // Sangat Rendah: 0.24, Rendah: 0.54, Sedang: 0.89, Tinggi: 1.27, Sangat Tinggi: 1.74
            $table->string('jkk_risk_level')->default('Sangat Rendah');
            $table->decimal('jkk_percent', 5, 2)->default(0.24);

            // JKM
            $table->boolean('jkm_active')->default(true);
            $table->decimal('jkm_percent', 5, 2)->default(0.30);

            // JP
            $table->boolean('jp_active')->default(true);
            $table->decimal('jp_company_percent', 5, 2)->default(2.00);
            $table->decimal('jp_employee_percent', 5, 2)->default(1.00);
            $table->unsignedBigInteger('jp_maximum_wage')->nullable()->default(10042300);

            $table->integer('effective_year')->default(2026);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_bpjs_ketenagakerjaan');
        Schema::dropIfExists('company_bpjs_kesehatan');
    }
};
