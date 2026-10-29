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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('name_ext')->nullable();

            $table->date('dob');
            $table->string('sex', 16);

            $table->string('philhealth_pin', 20)->nullable()->index();
            $table->boolean('is_member')->default(true);

            // Self-referencing relationship: a dependent points to the patient
            // who is the actual PhilHealth member.
            $table->foreignId('member_id')
                ->nullable()
                ->constrained('patients')
                ->nullOnDelete();

            /*
             * These address/relationship columns are not part of the original
             * field list, but Annex E prints them. They are nullable so older
             * records (or records that only need a PIN) remain valid.
             */
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('relationship')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
