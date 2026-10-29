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
            // Who signs the claim on behalf of the patient. Defaults to the
            // patient; minors can select their guardian/representative.
            $table->string('claim_signatory', 16)->default('patient')->after('contact_number');

            $table->string('guardian_name')->nullable()->after('claim_signatory');
            $table->string('guardian_relationship', 32)->nullable()->after('guardian_name');
            $table->string('guardian_contact_number', 30)->nullable()->after('guardian_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'claim_signatory',
                'guardian_name',
                'guardian_relationship',
                'guardian_contact_number',
            ]);
        });
    }
};
