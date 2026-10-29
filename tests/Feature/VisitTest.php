<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): Provider
    {
        return Provider::create([
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'pan' => 'P12034310',
        ]);
    }

    public function test_first_visit_is_tagged_oph01_and_second_visit_can_be_recorded(): void
    {
        $user = User::factory()->create();
        $provider = $this->provider();

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'dob' => '1990-01-01',
            'sex' => 'MALE',
            'is_member' => 1,
        ])->assertRedirect('/patients');

        $patient = Patient::firstWhere('first_name', 'Juan');
        $this->assertNotNull($patient);

        $firstVisit = $patient->visits()->first();
        $this->assertNotNull($firstVisit);
        $this->assertSame('OPH01', $firstVisit->claim_code);
        $this->assertSame(1, $firstVisit->visitNumber());

        $this->actingAs($user)->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertSee('OPH01')
            ->assertSee('1 of 2 yearly services recorded')
            ->assertSee('Next service: 2nd visit');

        $this->actingAs($user)->get("/patients/{$patient->id}/visits/create")
            ->assertOk()
            ->assertSee('OPH01')
            ->assertSee('OPH02');

        $this->actingAs($user)->post("/patients/{$patient->id}/visits", [
            'service_date' => now()->toDateString(),
            'provider_id' => $provider->id,
            'claim_code' => 'OPH02',
            'working_dx' => 'Dental caries',
        ])->assertRedirect("/patients/{$patient->id}");

        $this->assertSame(2, $patient->visits()->count());

        $secondVisit = $patient->visits()->latest('id')->first();
        $this->assertSame('OPH02', $secondVisit->claim_code);
        $this->assertSame(2, $secondVisit->visitNumber());

        $this->actingAs($user)->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertSee('2 of 2 yearly services recorded')
            ->assertSee('Both yearly services are already recorded');
    }

    public function test_visit_claim_code_can_be_corrected(): void
    {
        $user = User::factory()->create();
        $provider = $this->provider();

        $patient = Patient::create([
            'first_name' => 'Carlo',
            'last_name' => 'Mendoza',
            'dob' => '1988-08-08',
            'sex' => 'MALE',
            'is_member' => true,
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'service_date' => now()->toDateString(),
            'claim_code' => 'OPH01',
        ]);

        $this->actingAs($user)->get("/visits/{$visit->id}/edit")
            ->assertOk()
            ->assertSee('OPH01');

        $this->actingAs($user)->put("/visits/{$visit->id}", [
            'service_date' => now()->toDateString(),
            'provider_id' => $provider->id,
            'claim_code' => 'OPH01A',
        ])->assertRedirect("/patients/{$patient->id}");

        $this->assertSame('OPH01A', $visit->fresh()->claim_code);
    }

    public function test_visit_claim_code_must_be_one_of_the_supported_codes(): void
    {
        $user = User::factory()->create();
        $provider = $this->provider();

        $patient = Patient::create([
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'dob' => '1995-05-05',
            'sex' => 'FEMALE',
            'is_member' => true,
        ]);

        $this->actingAs($user)->post("/patients/{$patient->id}/visits", [
            'service_date' => now()->toDateString(),
            'provider_id' => $provider->id,
            'claim_code' => 'NOPE',
        ])->assertSessionHasErrors('claim_code');

        $this->assertSame(0, $patient->visits()->count());
    }

    public function test_visits_from_a_previous_year_do_not_count_towards_this_year(): void
    {
        $user = User::factory()->create();
        $provider = $this->provider();

        $patient = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'dob' => '1992-02-02',
            'sex' => 'FEMALE',
            'is_member' => true,
        ]);

        Visit::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'service_date' => now()->subYear()->toDateString(),
            'claim_code' => 'OPH01',
        ]);

        $this->actingAs($user)->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertSee('No service recorded this year')
            ->assertSee('Next service: 1st visit');

        $this->actingAs($user)->get('/patients')
            ->assertOk()
            ->assertSee('No visit');
    }

    public function test_patient_index_shows_first_and_second_visit_status(): void
    {
        $user = User::factory()->create();
        $provider = $this->provider();

        $firstPatient = Patient::create([
            'first_name' => 'First',
            'last_name' => 'Patient',
            'dob' => '1990-01-01',
            'sex' => 'MALE',
            'is_member' => true,
        ]);

        Visit::create([
            'patient_id' => $firstPatient->id,
            'provider_id' => $provider->id,
            'service_date' => now()->toDateString(),
            'claim_code' => 'OPH01',
        ]);

        $secondPatient = Patient::create([
            'first_name' => 'Second',
            'last_name' => 'Patient',
            'dob' => '1991-01-01',
            'sex' => 'FEMALE',
            'is_member' => true,
        ]);

        Visit::create([
            'patient_id' => $secondPatient->id,
            'provider_id' => $provider->id,
            'service_date' => now()->subDay()->toDateString(),
            'claim_code' => 'OPH01',
        ]);

        Visit::create([
            'patient_id' => $secondPatient->id,
            'provider_id' => $provider->id,
            'service_date' => now()->toDateString(),
            'claim_code' => 'OPH02',
        ]);

        $this->actingAs($user)->get('/patients')
            ->assertOk()
            ->assertSee('1st visit')
            ->assertSee('2nd visit')
            ->assertSee('OPH02');
    }
}
