<?php

namespace Database\Seeders;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Utilisateur::query()->updateOrCreate(
            ['telephone' => '+243900000001'],
            [
                'mot_de_passe_hash' => 'password',
                'nom' => 'Admin',
                'prenom' => 'FindMe',
                'postnom' => null,
                'email' => 'admin@findme.local',
                'role' => RoleUtilisateur::Administrateur,
                'statut_compte' => StatutCompte::Actif,
            ]
        );
    }
}