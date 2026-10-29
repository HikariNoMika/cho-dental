<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_patients_can_be_searched_by_name_pin_contact_and_barangay(): void
    {
        $user = User::factory()->create();

        Patient::create([
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'dob' => '1990-01-01',
            'sex' => 'MALE',
            'is_member' => true,
            'philhealth_pin' => '12-345678901-2',
            'contact_number' => '0917 123 4567',
            'barangay' => 'Assumption',
            'city' => 'Koronadal City',
            'province' => 'South Cotabato',
        ]);

        Patient::create([
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'dob' => '1995-05-05',
            'sex' => 'FEMALE',
            'contact_number' => '0999 888 7777',
            'barangay' => 'Mabini',
            'city' => 'Koronadal City',
            'province' => 'South Cotabato',
        ]);

        // Multiple name terms must all match, in any column order.
        $this->actingAs($user)->get('/patients?q=Juan+Cruz')
            ->assertOk()
            ->assertSee('Juan Santos Dela Cruz')
            ->assertDontSee('Maria Reyes');

        // PIN can be typed with or without separators.
        $this->actingAs($user)->get('/patients?q=123456789012')
            ->assertOk()
            ->assertSee('Juan Santos Dela Cruz')
            ->assertDontSee('Maria Reyes');

        $this->actingAs($user)->get('/patients?q=12-345678901-2')
            ->assertOk()
            ->assertSee('Juan Santos Dela Cruz')
            ->assertDontSee('Maria Reyes');

        // Contact number and address are searchable too.
        $this->actingAs($user)->get('/patients?q=0999')
            ->assertOk()
            ->assertSee('Maria Reyes')
            ->assertDontSee('Juan Santos Dela Cruz');

        $this->actingAs($user)->get('/patients?q=Mabini')
            ->assertOk()
            ->assertSee('Maria Reyes')
            ->assertDontSee('Juan Santos Dela Cruz');

        $this->actingAs($user)->get('/patients?q=Nobody')
            ->assertOk()
            ->assertSee('No patients matched');
    }

    public function test_patient_search_keeps_the_query_string_on_pagination(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 20) as $index) {
            Patient::create([
                'first_name' => 'Searchable',
                'last_name' => 'Patient '.$index,
                'dob' => '1990-01-01',
                'sex' => 'MALE',
            ]);
        }

        $this->actingAs($user)->get('/patients?q=Searchable')
            ->assertOk()
            ->assertSee('q=Searchable', false);
    }
}
