<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_dependent_without_existing_member_record_saves_manual_member_and_prints_it_on_claim_form_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'dob' => '2015-01-01',
            'sex' => 'FEMALE',
            'is_member' => 0,
            'philhealth_pin' => '12-345678901-9',
            'member_first_name' => 'Pedro',
            'member_middle_name' => 'Santos',
            'member_last_name' => 'Reyes',
            'member_pin' => '12-345678901-8',
            'relationship' => 'CHILD',
        ])->assertRedirect('/patients');

        $patient = Patient::firstWhere('first_name', 'Maria');
        $this->assertNotNull($patient);
        $this->assertNull($patient->member_id);
        $this->assertSame('Pedro', $patient->member_first_name);
        $this->assertSame('Santos', $patient->member_middle_name);
        $this->assertSame('Reyes', $patient->member_last_name);
        $this->assertSame('123456789018', $patient->member_pin);

        $manualMember = $patient->manualMember();
        $this->assertNotNull($manualMember);
        $this->assertSame('Pedro Santos Reyes', $manualMember->fullName());
        $this->assertSame('12-345678901-8', $manualMember->philhealthPin());

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

        $this->actingAs($user)->get("/claims/{$visit->id}/forms/claim-1")
            ->assertOk();

        $visit->refresh();
        $path = storage_path('app/prints/docx/'.$visit->referral_code.'/claim-form-1.docx');
        $this->assertFileExists($path);

        $archive = new \ZipArchive();
        $this->assertTrue($archive->open($path) === true);
        $documentXml = (string) $archive->getFromName('word/document.xml');
        $archive->close();

        $this->assertStringContainsString('PEDRO', $documentXml);
        $this->assertStringContainsString('REYES', $documentXml);
        $this->assertStringContainsString('12-345678901-8', $documentXml);
        $this->assertStringNotContainsString('MARIA', $documentXml);
    }
}
