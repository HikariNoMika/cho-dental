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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained('providers')
                ->restrictOnDelete();

            $table->date('service_date')->index();

            /*
             * Generated once and then kept forever. Reprinting an older visit
             * must not allocate a new daily sequence number.
             */
            $table->string('referral_code', 30)->nullable()->index();

            $table->text('working_dx')->nullable();
            $table->text('final_dx')->nullable();
            $table->string('disposition')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
