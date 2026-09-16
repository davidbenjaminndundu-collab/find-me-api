<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id('id_wallet');
            $table->foreignId('id_prestataire')
                ->unique()
                ->constrained('profils_prestataires', 'id_prestataire');
            $table->char('devise', 3)->default('USD');
            $table->decimal('solde_en_attente', 12, 2)->default(0);
            $table->decimal('solde_disponible', 12, 2)->default(0);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};