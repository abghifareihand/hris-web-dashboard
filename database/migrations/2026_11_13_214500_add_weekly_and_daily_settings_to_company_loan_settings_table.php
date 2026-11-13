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
        Schema::table('company_loan_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('max_loan_amount_weekly')->nullable()->after('max_loan_amount');
            $table->unsignedBigInteger('max_loan_amount_daily')->nullable()->after('max_loan_amount_weekly');
            $table->unsignedTinyInteger('max_tenor_weeks')->nullable()->after('max_tenor_months');
            $table->unsignedTinyInteger('max_tenor_days')->nullable()->after('max_tenor_weeks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_loan_settings', function (Blueprint $table) {
            $table->dropColumn([
                'max_loan_amount_weekly',
                'max_loan_amount_daily',
                'max_tenor_weeks',
                'max_tenor_days',
            ]);
        });
    }
};

