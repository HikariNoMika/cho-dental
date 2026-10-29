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
            // Manual member/principal details for dependents whose member is
            // not registered as a patient in this application.
            $table->string('member_first_name')->nullable()->after('member_id');
            $table->string('member_middle_name')->nullable()->after('member_first_name');
            $table->string('member_last_name')->nullable()->after('member_middle_name');
            $table->string('member_name_ext', 32)->nullable()->after('member_last_name');
            $table->string('member_pin', 20)->nullable()->after('member_name_ext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'member_first_name',
                'member_middle_name',
                'member_last_name',
                'member_name_ext',
                'member_pin',
            ]);
        });
    }
};
