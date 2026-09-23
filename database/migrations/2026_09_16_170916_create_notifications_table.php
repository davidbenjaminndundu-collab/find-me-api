<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('id_notification');
            $table->foreignId('id_utilisateur')
                ->constrained('utilisateurs', 'id_utilisateur');
            $table->string('type_notification', 40);
            $table->string('titre', 150);
            $table->text('message');
            $table->string('lien', 255)->nullable();
            $table->timestampTz('lu_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE notifications ADD COLUMN canal canal_notification NOT NULL DEFAULT 'in_app'");
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};