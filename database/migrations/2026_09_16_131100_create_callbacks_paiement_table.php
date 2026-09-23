<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callbacks_paiement', function (Blueprint $table) {
            $table->id('id_callback');
            $table->foreignId('id_paiement')
                ->constrained('paiements', 'id_paiement');
            $table->string('operateur', 30);
            $table->string('identifiant_evenement', 100);
            $table->boolean('signature_valide');
            $table->string('statut_recu', 30)->nullable();
            $table->decimal('montant_recu', 12, 2)->nullable();
            $table->string('reference_operateur', 100)->nullable();
            $table->jsonb('payload');
            $table->string('resultat_traitement', 30);
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['operateur', 'identifiant_evenement']);
        });

        DB::statement("ALTER TABLE callbacks_paiement ADD CONSTRAINT ck_callbacks_resultat CHECK (resultat_traitement IN ('traite', 'rejete', 'ignore', 'duplique', 'incoherent'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('callbacks_paiement');
    }
};