<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pharmacy;
use App\Models\OpeningHour;
use App\Models\DutySchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_pharmacien_valide_peut_voir_ses_horaires()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pharma/my-pharmacy/hours');

        $response->assertStatus(200);
    }

    public function test_un_pharmacien_peut_mettre_a_jour_ses_horaires()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $hours = [
            ['jour_semaine' => 1, 'heure_ouverture' => '08:00', 'heure_fermeture' => '18:00'],
            ['jour_semaine' => 2, 'heure_ouverture' => '08:00', 'heure_fermeture' => '18:00'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/pharma/my-pharmacy/hours', ['hours' => $hours]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('opening_hours', [
            'pharmacy_id' => $pharmacy->id,
            'jour_semaine' => 1,
            'heure_ouverture' => '08:00',
        ]);
    }

    public function test_un_pharmacien_non_valide_ne_peut_pas_modifier_ses_horaires()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/pharma/my-pharmacy/hours', ['hours' => []]);

        $response->assertStatus(403);
    }

    public function test_plusieurs_plages_horaires_par_jour_sont_autorisees()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $hours = [
            ['jour_semaine' => 1, 'heure_ouverture' => '08:00', 'heure_fermeture' => '12:00'],
            ['jour_semaine' => 1, 'heure_ouverture' => '14:00', 'heure_fermeture' => '18:00'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/pharma/my-pharmacy/hours', ['hours' => $hours]);

        $response->assertStatus(200);

        $this->assertEquals(2, $pharmacy->openingHours()->where('jour_semaine', 1)->count());
    }

    public function test_fermeture_apres_minuit_est_autorisee()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $hours = [
            ['jour_semaine' => 1, 'heure_ouverture' => '20:00', 'heure_fermeture' => '02:00'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/pharma/my-pharmacy/hours', ['hours' => $hours]);

        $response->assertStatus(200);
    }

    public function test_un_pharmacien_peut_voir_ses_gardes()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pharma/my-pharmacy/duty');

        $response->assertStatus(200);
    }

    public function test_un_pharmacien_peut_ajouter_une_garde()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/pharma/my-pharmacy/duty', [
                'date_debut' => now()->addDay()->toDateString(),
                'date_fin' => now()->addDays(2)->toDateString(),
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('duty_schedules', [
            'pharmacy_id' => $pharmacy->id,
        ]);
    }

    public function test_date_fin_doit_etre_apres_date_debut()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/pharma/my-pharmacy/duty', [
                'date_debut' => now()->addDays(2)->toDateString(),
                'date_fin' => now()->addDay()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_fin']);
    }

    public function test_chevauchement_de_gardes_est_interdit()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        // Créer une garde existante
        DutySchedule::factory()->create([
            'pharmacy_id' => $pharmacy->id,
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
        ]);

        // Essayer de créer une garde qui chevauche
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/pharma/my-pharmacy/duty', [
                'date_debut' => now()->addDays(2)->toDateString(),
                'date_fin' => now()->addDays(4)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Duty schedule overlaps with existing schedule']);
    }

    public function test_un_pharmacien_peut_supprimer_une_garde()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $duty = DutySchedule::factory()->create([
            'pharmacy_id' => $pharmacy->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/pharma/my-pharmacy/duty/{$duty->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('duty_schedules', ['id' => $duty->id]);
    }

    public function test_un_pharmacien_ne_peut_pas_modifier_les_horaires_d_une_autre_pharmacie()
    {
        $userA = User::factory()->create(['role' => 'pharmacist']);
        $pharmacyA = Pharmacy::factory()->create([
            'user_id' => $userA->id,
            'est_valide' => true,
        ]);

        $userB = User::factory()->create(['role' => 'pharmacist']);
        $pharmacyB = Pharmacy::factory()->create([
            'user_id' => $userB->id,
            'est_valide' => true,
        ]);

        $hours = [
            ['jour_semaine' => 1, 'heure_ouverture' => '08:00', 'heure_fermeture' => '18:00'],
        ];

        $response = $this->actingAs($userA, 'sanctum')
            ->putJson('/api/pharma/my-pharmacy/hours', ['hours' => $hours]);

        $response->assertStatus(200);

        // Vérifier que les horaires sont créés pour pharmacyA, pas pharmacyB
        $this->assertDatabaseHas('opening_hours', [
            'pharmacy_id' => $pharmacyA->id,
        ]);

        $this->assertDatabaseMissing('opening_hours', [
            'pharmacy_id' => $pharmacyB->id,
        ]);
    }
}
