<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patients')) {
            DB::statement('CREATE INDEX IF NOT EXISTS patients_created_at_index ON patients (created_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS patients_philhealth_pin_index ON patients (philhealth_pin)');
        }

        if (Schema::hasTable('visits')) {
            DB::statement('CREATE INDEX IF NOT EXISTS visits_patient_id_service_date_index ON visits (patient_id, service_date)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS patients_created_at_index');
        DB::statement('DROP INDEX IF EXISTS patients_philhealth_pin_index');
        DB::statement('DROP INDEX IF EXISTS visits_patient_id_service_date_index');
    }
};
