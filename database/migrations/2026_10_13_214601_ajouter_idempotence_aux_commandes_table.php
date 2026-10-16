
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->uuid('cle_idempotence')->nullable();

            $table->string('empreinte_requete', 64)->nullable();

            $table->unique(
                ['id_client', 'cle_idempotence'],
                'commandes_client_idempotence_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropUnique(
                'commandes_client_idempotence_unique'
            );

            $table->dropColumn([
                'cle_idempotence',
                'empreinte_requete'
            ]);
        });
    }
};
