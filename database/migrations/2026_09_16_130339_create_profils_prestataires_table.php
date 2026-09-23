<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profils_prestataires', function (Blueprint $table) {
            $table->id('id_prestataire');
            $table->foreignId('id_utilisateur')
                ->unique()
                ->constrained('utilisateurs', 'id_utilisateur');
            $table->string('nom_professionnel', 150);
            $table->text('bio');
            $table->text('competences');
            $table->string('portfolio_url', 500)->nullable();
            $table->decimal('note_moyenne', 3, 2)->default(0);
            $table->unsignedInteger('nombre_avis')->default(0);
            $table->string('statut_disponibilite', 20);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profils_prestataires');
    }
};