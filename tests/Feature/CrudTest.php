<?php

namespace Tests\Feature;

use App\Models\DentalAide;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_crud_pages_work(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/patients')->assertOk();
        $this->actingAs($user)->get('/patients/create')
            ->assertOk()
            ->assertSee('Region XII (SOCCSKSARGEN)')
            ->assertSee('South Cotabato')
            ->assertSee('Koronadal City')
            ->assertSee('Assumption')
            ->assertSee('Member / Principal (if dependent)')
            ->assertSee('Member PIN');

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'dob' => '1990-01-01',
            'sex' => 'MALE',
            'is_member' => 1,
            'philhealth_pin' => '12-345678901-2',
            'region' => 'Region XII (SOCCSKSARGEN)',
            'province' => 'South Cotabato',
            'city' => 'Koronadal City',
            'barangay' => 'Assumption',
            'contact_number' => '0917 123 4567',
        ])->assertRedirect('/patients');

        $patient = Patient::firstWhere('first_name', 'Juan');
        $this->assertNotNull($patient);
        $this->assertSame('12-345678901-2', $patient->philhealth_pin);
        $this->assertSame('Region XII (SOCCSKSARGEN)', $patient->region);
        $this->assertSame('South Cotabato', $patient->province);
        $this->assertSame('Koronadal City', $patient->city);
        $this->assertSame('Assumption', $patient->barangay);
        $this->assertSame('0917 123 4567', $patient->contact_number);

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'dob' => '2015-01-01',
            'sex' => 'FEMALE',
            'member_first_name' => 'Juan',
            'member_last_name' => 'Dela Cruz',
            'member_pin' => '12-345678901-2',
            'relationship' => 'CHILD',
        ])->assertRedirect('/patients');

        $dependent = Patient::firstWhere('first_name', 'Maria');
        $this->assertNotNull($dependent);
        $this->assertSame($patient->id, $dependent->member_id);

        $provider = Provider::create([
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'pan' => 'P12034310',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'service_date' => '2026-10-24',
        ]);

        $this->actingAs($user)->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertSee('Claim Forms')
            ->assertSee('0917 123 4567')
            ->assertSee('Region XII (SOCCSKSARGEN)')
            ->assertSee(route('claims.forms', $visit), false);
        $this->actingAs($user)->get('/patients')
            ->assertOk()
            ->assertSee(route('claims.forms', $visit), false);
        $this->actingAs($user)->get("/patients/{$patient->id}/edit")->assertOk();

        $this->actingAs($user)->put("/patients/{$patient->id}", [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'dob' => '1990-01-01',
            'sex' => 'MALE',
            'region' => 'Region XII (SOCCSKSARGEN)',
            'province' => 'South Cotabato',
            'city' => 'Koronadal City',
            'barangay' => 'San Isidro',
            'contact_number' => '0999 888 7777',
        ])->assertRedirect('/patients');

        $patient->refresh();
        $this->assertSame('San Isidro', $patient->barangay);
        $this->assertSame('0999 888 7777', $patient->contact_number);
    }

    public function test_provider_crud_pages_work(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/providers')->assertOk();
        $this->actingAs($user)->get('/providers/create')->assertOk();

        $this->actingAs($user)->post('/providers', [
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'pan' => 'P12034310',
        ])->assertRedirect('/providers');

        $provider = Provider::firstWhere('last_name', 'Rizal');
        $this->assertNotNull($provider);
        $this->assertSame('DMD', $provider->credentials);

        $this->actingAs($user)->get('/providers')
            ->assertOk()
            ->assertSee('Jose Rizal, DMD');
        $this->actingAs($user)->get("/providers/{$provider->id}")->assertOk();
        $this->actingAs($user)->get("/providers/{$provider->id}/edit")->assertOk();
    }

    public function test_dental_aide_crud_pages_work(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dental-aides')->assertOk();
        $this->actingAs($user)->get('/dental-aides/create')->assertOk();

        $this->actingAs($user)->post('/dental-aides', [
            'first_name' => 'Ana',
            'last_name' => 'Santos',
        ])->assertRedirect('/dental-aides');

        $aide = DentalAide::firstWhere('last_name', 'Santos');
        $this->assertNotNull($aide);

        $this->actingAs($user)->get("/dental-aides/{$aide->id}")->assertOk();
        $this->actingAs($user)->get("/dental-aides/{$aide->id}/edit")->assertOk();
    }

    public function test_user_crud_pages_work(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/users')->assertOk();
        $this->actingAs($user)->get('/users/create')->assertOk();

        $this->actingAs($user)->post('/users', [
            'first_name' => 'Staff',
            'last_name' => 'One',
            'designation' => 'Encoder',
            'email' => 'staff@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/users');

        $staff = User::firstWhere('email', 'staff@example.com');
        $this->assertNotNull($staff);
        $this->assertSame('Staff One', $staff->fullName());
        $this->assertSame('Encoder', $staff->designation);

        $this->actingAs($user)->get("/users/{$staff->id}")->assertOk();
        $this->actingAs($user)->get("/users/{$staff->id}/edit")->assertOk();
    }

    public function test_claim_forms_and_docx_downloads_work(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'designation' => 'Encoder',
        ]);

        $patient = Patient::create([
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'dob' => '1990-01-01',
            'sex' => 'FEMALE',
            'is_member' => true,
            'philhealth_pin' => '12-345678901-2',
            'barangay' => 'Poblacion',
            'city' => 'Makati',
            'province' => 'Metro Manila',
        ]);

        $provider = Provider::create([
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'pan' => 'P12034310',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'service_date' => '2026-10-24',
        ]);

        $this->actingAs($user)->get("/claims/{$visit->id}/forms")
            ->assertOk()
            ->assertSee('Referral Form')
            ->assertSee('Claim Form 1')
            ->assertSee('Claim Form 2');

        $this->actingAs($user)->get("/claims/{$visit->id}/forms/referral")
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $visit->refresh();
        $referralPath = storage_path('app/prints/docx/'.$visit->referral_code.'/referral-form.docx');
        $this->assertFileExists($referralPath);

        $archive = new \ZipArchive();
        $this->assertTrue($archive->open($referralPath) === true);
        $documentXml = (string) $archive->getFromName('word/document.xml');
        $archive->close();

        $this->assertStringContainsString('ANA REYES', $documentXml);
        $this->assertStringContainsString('Encoder', $documentXml);

        $this->actingAs($user)->get("/claims/{$visit->id}/forms.zip")
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_claim_forms_require_authentication(): void
    {
        $this->get('/claims/1/forms')->assertRedirect('/login');
        $this->get('/claims/1/forms/referral')->assertRedirect('/login');
        $this->get('/claims/1/forms.zip')->assertRedirect('/login');
    }
}
