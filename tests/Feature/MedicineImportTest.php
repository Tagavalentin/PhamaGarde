<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Medicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MedicineImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_admin_peut_importer_un_csv()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csvContent = "nom_commercial,dci,forme,dosage,categorie\nPARACETAMOL,PARACETAMOL,Comprimé,500 mg,Analgésique\nIBUPROFENE,IBUPROFENE,Comprimé,400 mg,Analgésique\n";
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('medicines.csv', $csvContent);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'imported',
                'updated',
                'rejected',
            ]);

        $this->assertDatabaseHas('medicines', [
            'nom_commercial' => 'PARACETAMOL',
            'dci' => 'PARACETAMOL',
        ]);
    }

    public function test_un_non_admin_ne_peut_pas_importer()
    {
        $user = User::factory()->create(['role' => 'user']);

        $csvContent = "nom_commercial,dci,forme,dosage,categorie\nPARACETAMOL,PARACETAMOL,Comprimé,500 mg,Analgésique\n";
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('medicines.csv', $csvContent);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(403);
    }

    public function test_le_fichier_doit_etre_un_csv()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $file = \Illuminate\Http\UploadedFile::fake()->create('medicines.pdf', 100);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv']);
    }

    public function test_le_fichier_ne_doit_pas_depasser_2_mo()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $file = \Illuminate\Http\UploadedFile::fake()->create('medicines.csv', 3000);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv']);
    }

    public function test_les_entetes_doivent_etre_valides()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csvContent = "nom,categorie\nPARACETAMOL,Analgésique\n";
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('medicines.csv', $csvContent);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_la_normalisation_fonctionne()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csvContent = "nom_commercial,dci,forme,dosage,categorie\nparacetamol,paracetamol,comprimé,500 mg,analgésique\n";
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('medicines.csv', $csvContent);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('medicines', [
            'nom_commercial' => 'PARACETAMOL',
            'dci' => 'PARACETAMOL',
        ]);
    }

    public function test_upsert_ne_cree_pas_de_doublons()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Créer un médicament existant
        Medicine::create([
            'nom_commercial' => 'PARACETAMOL',
            'dci' => 'PARACETAMOL',
            'forme' => 'Comprimé',
            'dosage' => '500 mg',
            'categorie' => 'Analgésique',
        ]);

        $csvContent = "nom_commercial,dci,forme,dosage,categorie\nPARACETAMOL,PARACETAMOL,Comprimé,500 mg,Analgésique\n";
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('medicines.csv', $csvContent);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(200);
        
        // Vérifier qu'il n'y a qu'un seul PARACETAMOL
        $count = Medicine::where('nom_commercial', 'PARACETAMOL')->count();
        $this->assertEquals(1, $count);
    }

    public function test_les_lignes_invalides_sont_rejetees()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csvContent = "nom_commercial,dci,forme,dosage,categorie\nPARACETAMOL,PARACETAMOL,Comprimé,500 mg,Analgésique\n,,Comprimé,500 mg,Analgésique\n";
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('medicines.csv', $csvContent);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(200);
        
        $rejected = $response->json('rejected');
        $this->assertGreaterThan(0, count($rejected));
    }

    public function test_le_rapport_inclut_importes_et_mis_a_jour()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Créer un médicament existant
        Medicine::create([
            'nom_commercial' => 'PARACETAMOL',
            'dci' => 'PARACETAMOL',
            'forme' => 'Comprimé',
            'dosage' => '500 mg',
            'categorie' => 'Analgésique',
        ]);

        $csvContent = "nom_commercial,dci,forme,dosage,categorie\nPARACETAMOL,PARACETAMOL,Comprimé,500 mg,Analgésique\nIBUPROFENE,IBUPROFENE,Comprimé,400 mg,Analgésique\n";
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('medicines.csv', $csvContent);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pharma/admin/medicines/import', [
                'csv' => $file,
            ]);

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals(1, $data['imported']);
        $this->assertEquals(1, $data['updated']);
    }
}
