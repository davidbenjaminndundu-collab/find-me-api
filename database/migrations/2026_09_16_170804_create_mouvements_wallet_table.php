<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_wallet', function (Blueprint $table) {
            $table->id('id_mouvement');
            $table->foreignId('id_wallet')
                ->constrained('wallets', 'id_wallet');
            $table->decimal('montant', 12, 2);
            $table->decimal('solde_avant', 12, 2);
            $table->decimal('solde_apres', 12, 2);
            $table->string('reference', 50)->unique();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('ALTER TABLE mouvements_wallet ADD COLUMN type_mouvement type_mouvement_wallet NOT NULL');
        DB::statement('ALTER TABLE mouvements_wallet ADD COLUMN sens sens_mouvement_wallet NOT NULL');
        DB::statement("ALTER TABLE mouvements_wallet ADD COLUMN statut statut_mouvement_wallet NOT NULL DEFAULT 'en_attente'");
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_wallet');
    }
};