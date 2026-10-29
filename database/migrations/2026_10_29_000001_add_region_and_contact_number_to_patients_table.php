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
        Schema::table('patients', function (Blueprint $table) {
            // Region is stored separately from province so address data can be
            // grouped and formatted consistently on patient and claim records.
            $table->string('region')->nullable()->after('province');

            // Keep the contact number as text so leading zeros (e.g. 09xx)
            // and common separators are preserved.
            $table->string('contact_number', 30)->nullable()->after('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['region', 'contact_number']);
        });
    }
};
