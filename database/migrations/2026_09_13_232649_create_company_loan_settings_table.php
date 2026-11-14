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
        Schema::create('company_loan_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade')->unique();
            $table->unsignedBigInteger('max_loan_amount')->nullable(); // null = tidak terbatas (Bulanan)
            $table->unsignedBigInteger('max_loan_amount_weekly')->nullable(); // null = tidak terbatas (Mingguan)
            $table->unsignedBigInteger('max_loan_amount_daily')->nullable(); // null = tidak terbatas (Harian)
            $table->unsignedTinyInteger('max_tenor_months')->nullable(); // null = tidak terbatas (Bulan)
            $table->unsignedTinyInteger('max_tenor_weeks')->nullable(); // null = tidak terbatas (Minggu)
            $table->unsignedTinyInteger('max_tenor_days')->nullable(); // null = tidak terbatas (Hari)
            $table->unsignedTinyInteger('due_date_day')->nullable(); // null = default tgl 1 (1 - 31)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_loan_settings');
    }
};
