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
            $table->unsignedBigInteger('max_loan_amount')->nullable(); // null = tidak terbatas
            $table->unsignedTinyInteger('max_tenor_months')->nullable(); // null = tidak terbatas
            $table->unsignedTinyInteger('due_date_day')->nullable(); // null = default tgl 1
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
