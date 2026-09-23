<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id('id_livraison');
            $table->foreignId('id_commande')
                ->constrained('commandes', 'id_commande');
            $table->unsignedInteger('numero_version');
            $table->text('message');
            $table->string('url_fichier', 500);
            $table->string('nom_fichier', 255);
            $table->string('type_mime', 100);
            $table->unsignedBigInteger('taille_octets');
            $table->timestampTz('delivered_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['id_commande', 'numero_version']);
        });

        DB::statement("ALTER TABLE livraisons ADD COLUMN statut statut_livraison NOT NULL DEFAULT 'soumise'");
    }

    public function down(): void
    {
        Schema::dropIfExists('livraisons');
    }
};