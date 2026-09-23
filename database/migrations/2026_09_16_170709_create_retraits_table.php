<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retraits', function (Blueprint $table) {
            $table->id('id_retrait');
            $table->foreignId('id_wallet')
                ->constrained('wallets', 'id_wallet');
            $table->string('reference_retrait', 40)->unique();
            $table->decimal('montant', 12, 2);
            $table->decimal('frais', 12, 2)->default(0);
            $table->decimal('montant_net', 12, 2);
            $table->string('operateur', 30);
            $table->string('numero_mobile_money', 20);
            $table->string('reference_operateur', 100)->nullable();
            $table->text('motif_refus')->nullable();
            $table->timestampTz('requested_at')->useCurrent();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('executed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE retraits ADD COLUMN statut statut_retrait NOT NULL DEFAULT 'demande'");
        DB::statement('CREATE UNIQUE INDEX uq_retraits_reference_operateur ON retraits (operateur, reference_operateur) WHERE reference_operateur IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('retraits');
    }
};