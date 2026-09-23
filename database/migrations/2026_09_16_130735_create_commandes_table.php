<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id('id_commande');
            $table->foreignId('id_prestataire')
                ->constrained('profils_prestataires', 'id_prestataire');
            $table->foreignId('id_client')
                ->constrained('utilisateurs', 'id_utilisateur');
            $table->foreignId('id_annonce')
                ->constrained('annonces', 'id_annonce');
            $table->string('reference_commande', 30)->unique();
            $table->text('description_commande');
            $table->string('titre_service', 200);
            $table->boolean('est_offre_personnalisee')->default(false);
            $table->decimal('montant_total', 12, 2);
            $table->decimal('taux_commission', 5, 2)->default(10.00);
            $table->decimal('montant_commission', 12, 2);
            $table->decimal('montant_prestataire', 12, 2);
            $table->unsignedInteger('delai_livraison_jours');
            $table->unsignedInteger('nombre_revisions_incluses')->default(0);
            $table->unsignedInteger('nombre_revisions_utilisees')->default(0);
            $table->text('livrables_convenus');
            $table->timestampTz('date_acceptation')->nullable();
            $table->timestampTz('date_expiration_offre')->nullable();
            $table->timestampTz('date_paiement')->nullable();
            $table->timestampTz('date_limite_livraison')->nullable();
            $table->timestampTz('date_livraison')->nullable();
            $table->timestampTz('date_fin')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE commandes ADD COLUMN statut statut_cycle_commande NOT NULL DEFAULT 'brouillon'");
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};