<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->id('id_avis');
            $table->foreignId('id_commande')
                ->unique()
                ->constrained('commandes', 'id_commande');
            $table->foreignId('id_client')
                ->constrained('utilisateurs', 'id_utilisateur');
            $table->unsignedTinyInteger('note');
            $table->text('commentaire')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE avis ADD COLUMN statut statut_avis NOT NULL DEFAULT 'publie'");
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};