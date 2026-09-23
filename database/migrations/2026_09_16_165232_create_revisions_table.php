<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id('id_revision');
            $table->foreignId('id_commande')
                ->constrained('commandes', 'id_commande');
            $table->text('description');
            $table->timestampTz('requested_at')->useCurrent();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE revisions ADD COLUMN statut statut_revision NOT NULL DEFAULT 'demandee'");
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};