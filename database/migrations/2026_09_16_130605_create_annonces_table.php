<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->id('id_annonce');
            $table->foreignId('id_categorie')
                ->constrained('categories', 'id_categorie');
            $table->foreignId('id_prestataire')
                ->constrained('profils_prestataires', 'id_prestataire');
            $table->string('titre', 200);
            $table->text('description');
            $table->decimal('prix_base', 12, 2);
            $table->unsignedInteger('delai_livraison_jours');
            $table->unsignedInteger('nombre_revisions')->default(0);
            $table->text('livrables_inclus');
            $table->text('elements_requis_client');
            $table->string('image_couverture', 500)->nullable();
            $table->text('mots_cles')->nullable();
            $table->text('conditions_particulieres')->nullable();
            $table->text('motif_refus')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE annonces ADD COLUMN statut statut_annonce NOT NULL DEFAULT 'brouillon'");
    }

    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};