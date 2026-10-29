<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientGuardianTest extends TestCase
{
    use RefreshDatabase;

    public function test_minor_guardian_details_are_saved_and_used_on_claim_form_two(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/patients/create')
            ->assertOk()
            ->assertSee('Claim Form Signature')
            ->assertSee('Who will sign?')
            ->assertSee('Guardian Details')
            ->assertSee('Guardian Full Name');

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'dob' => now()->subYears(10)->toDateString(),
            'sex' => 'FEMALE',
            'is_member' => 1,
            'philhealth_pin' => '12-345678901-3',
            'claim_signatory' => 'guardian',
            'guardian_name' => 'Maria Santos',
            'guardian_relationship' => 'MOTHER',
            'guardian_contact_number' => '0917 111 2222',
        ])->assertRedirect('/patients');

        $patient = Patient::firstWhere('first_name', 'Ana');
        $this->assertNotNull($patient);
        $this->assertSame('guardian', $patient->claim_signatory);
        $this->assertSame('Maria Santos', $patient->guardian_name);
        $this->assertSame('MOTHER', $patient->guardian_relationship);

        $provider = Provider::create([
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'pan' => 'P12034310',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'service_date' => '2026-10-29',
        ]);

        $this->actingAs($user)->get("/claims/{$visit->id}/forms/claim-2")
            ->assertOk();

        $visit->refresh();
        $path = storage_path('app/prints/docx/'.$visit->referral_code.'/claim-form-2.docx');
        $this->assertFileExists($path);

        $archive = new \ZipArchive();
        $this->assertTrue($archive->open($path) === true);
        $documentXml = (string) $archive->getFromName('word/document.xml');
        $archive->close();

        $this->assertStringContainsString('MARIA SANTOS', $documentXml);
        $this->assertStringNotContainsString('ANA REYES', $documentXml);
    }

    public function test_minor_guardian_signatory_requires_guardian_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'dob' => now()->subYears(10)->toDateString(),
            'sex' => 'FEMALE',
            'is_member' => 1,
            'philhealth_pin' => '12-345678901-3',
            'claim_signatory' => 'guardian',
        ])->assertSessionHasErrors(['guardian_name', 'guardian_relationship']);

        $this->assertDatabaseMissing('patients', ['first_name' => 'Ana']);
    }

    public function test_minor_can_be_saved_with_patient_as_signatory_without_guardian(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Liza',
            'last_name' => 'Reyes',
            'dob' => now()->subYears(8)->toDateString(),
            'sex' => 'FEMALE',
            'is_member' => 1,
            'philhealth_pin' => '12-345678901-4',
            'claim_signatory' => 'patient',
        ])->assertRedirect('/patients');

        $patient = Patient::firstWhere('first_name', 'Liza');
        $this->assertNotNull($patient);
        $this->assertSame('patient', $patient->claim_signatory);
        $this->assertNull($patient->guardian_name);
    }

    public function test_adult_patients_cannot_use_a_guardian_signatory(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'dob' => now()->subYears(30)->toDateString(),
            'sex' => 'MALE',
            'is_member' => 1,
            'philhealth_pin' => '12-345678901-2',
            'claim_signatory' => 'guardian',
            'guardian_name' => 'Maria Santos',
            'guardian_relationship' => 'MOTHER',
        ])->assertRedirect('/patients');

        $patient = Patient::firstWhere('first_name', 'Juan');
        $this->assertNotNull($patient);
        $this->assertSame('patient', $patient->claim_signatory);
    }
}
