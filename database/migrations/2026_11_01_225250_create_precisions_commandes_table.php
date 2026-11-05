<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precisions_commandes', function (Blueprint $table) {
            $table->id('id_precision');

            $table->foreignId('id_commande')
                ->constrained('commandes', 'id_commande')
                ->cascadeOnDelete();

            $table->text('demande');

            $table->text('reponse')
                ->nullable();

            $table->timestampTz('demande_at');

            $table->timestampTz('reponse_at')
                ->nullable();

            $table->timestampsTz();

            $table->index([
                'id_commande',
                'reponse_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precisions_commandes');
    }
};