<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('litiges', function (Blueprint $table) {
            $table->id('id_litige');
            $table->foreignId('id_commande')
                ->unique()
                ->constrained('commandes', 'id_commande');
            $table->string('motif', 100);
            $table->text('description');
            $table->jsonb('preuves')->nullable();
            $table->decimal('montant_client', 12, 2)->default(0);
            $table->decimal('montant_prestataire', 12, 2)->default(0);
            $table->timestampTz('opened_at')->useCurrent();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE litiges ADD COLUMN statut statut_litige NOT NULL DEFAULT 'ouvert'");
        DB::statement('ALTER TABLE litiges ADD COLUMN decision decision_litige NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('litiges');
    }
};