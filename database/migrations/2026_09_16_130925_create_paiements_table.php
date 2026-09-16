<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id('id_paiement');
            $table->foreignId('id_commande')
                ->constrained('commandes', 'id_commande');
            $table->string('reference_interne', 40)->unique();
            $table->decimal('montant', 12, 2);
            $table->char('devise', 3)->default('USD');
            $table->string('operateur', 30);
            $table->string('numero_payeur_masque', 30)->nullable();
            $table->string('reference_operateur', 100)->nullable()->unique();
            $table->string('idempotency_key', 100)->unique();
            $table->timestampTz('initiated_at')->useCurrent();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE paiements ADD COLUMN statut statut_paiement NOT NULL DEFAULT 'cree'");
        DB::statement('CREATE UNIQUE INDEX uq_paiements_commande_confirmee ON paiements (id_commande) WHERE statut = \'confirme\'');
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};