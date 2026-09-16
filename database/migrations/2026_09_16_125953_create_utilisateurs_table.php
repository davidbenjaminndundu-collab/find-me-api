<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id('id_utilisateur');
            $table->string('telephone', 20)->unique();
            $table->string('mot_de_passe_hash');
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('postnom', 100)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('image_profil', 500)->nullable();
            $table->timestampTz('telephone_verifie_at')->nullable();
            $table->timestampTz('derniere_connexion_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE utilisateurs ADD COLUMN role role_utilisateur NOT NULL');
        DB::statement("ALTER TABLE utilisateurs ADD COLUMN statut_compte statut_compte NOT NULL DEFAULT 'a_verifier'");
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};