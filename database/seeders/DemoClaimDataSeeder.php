<?php

namespace Database\Seeders;

use App\Models\DentalService;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoClaimDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provider = Provider::firstOrCreate(
            ['pan' => 'P12034310'],
            [
                'first_name' => 'Jose',
                'middle_name' => 'Protacio',
                'last_name' => 'Rizal',
                'name_ext' => null,
            ],
        );

        $patient = Patient::firstOrCreate(
            ['philhealth_pin' => '12-345678901-2'],
            [
                'first_name' => 'Maria',
                'middle_name' => 'Santos',
                'last_name' => 'Dela Cruz',
                'name_ext' => 'Jr.',
                'dob' => Carbon::parse('1989-05-15'),
                'sex' => 'FEMALE',
                'is_member' => true,
                'barangay' => 'San Isidro',
                'city' => 'Koronadal City',
                'province' => 'South Cotabato',
                'region' => 'Region XII (SOCCSKSARGEN)',
                'contact_number' => '0917 123 4567',
                'relationship' => null,
            ],
        );

        $visit = Visit::firstOrCreate(
            [
                'patient_id' => $patient->id,
                'provider_id' => $provider->id,
                'service_date' => Carbon::today(),
            ],
            [
                'working_dx' => 'Dental Caries',
                'final_dx' => 'Dental Caries',
                'disposition' => 'Improved',
            ],
        );

        DentalService::firstOrCreate(
            [
                'visit_id' => $visit->id,
                'tooth_number' => '16',
                'service_code' => 'RES',
            ],
        );
    }
}
