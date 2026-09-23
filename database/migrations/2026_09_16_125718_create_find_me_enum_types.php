<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP TYPE IF EXISTS canal_notification CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_avis CASCADE');
        DB::statement('DROP TYPE IF EXISTS type_mouvement_wallet CASCADE');
        DB::statement('DROP TYPE IF EXISTS sens_mouvement_wallet CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_mouvement_wallet CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_retrait CASCADE');
        DB::statement('DROP TYPE IF EXISTS decision_litige CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_litige CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_revision CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_livraison CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_paiement CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_cycle_commande CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_annonce CASCADE');
        DB::statement('DROP TYPE IF EXISTS statut_compte CASCADE');
        DB::statement('DROP TYPE IF EXISTS role_utilisateur CASCADE');

        DB::statement("CREATE TYPE role_utilisateur AS ENUM ('client', 'prestataire', 'administrateur')");
        DB::statement("CREATE TYPE statut_compte AS ENUM ('a_verifier', 'actif', 'suspendu', 'bloque', 'ferme')");
        DB::statement("CREATE TYPE statut_annonce AS ENUM ('brouillon', 'en_attente_validation', 'publiee', 'refusee', 'desactivee', 'supprimee')");
        DB::statement("CREATE TYPE statut_cycle_commande AS ENUM ('brouillon', 'envoyee', 'en_attente_reponse', 'informations_demandees', 'acceptee', 'refusee', 'offre_proposee', 'offre_acceptee', 'offre_expiree', 'en_attente_paiement', 'paiement_en_cours', 'payee', 'en_cours_realisation', 'livree', 'revision_demandee', 'en_revision', 'en_litige', 'terminee', 'annulee', 'remboursee')");
        DB::statement("CREATE TYPE statut_paiement AS ENUM ('cree', 'en_attente', 'confirme', 'echoue', 'expire', 'rembourse')");
        DB::statement("CREATE TYPE statut_livraison AS ENUM ('soumise', 'acceptee', 'a_reviser', 'remplacee')");
        DB::statement("CREATE TYPE statut_revision AS ENUM ('demandee', 'acceptee', 'en_cours', 'livree', 'refusee', 'annulee')");
        DB::statement("CREATE TYPE statut_litige AS ENUM ('ouvert', 'en_analyse', 'informations_demandees', 'resolu', 'ferme')");
        DB::statement("CREATE TYPE decision_litige AS ENUM ('remboursement_total', 'paiement_total', 'repartition_partielle')");
        DB::statement("CREATE TYPE statut_retrait AS ENUM ('demande', 'en_verification', 'approuve', 'en_cours_execution', 'execute', 'echoue', 'refuse')");
        DB::statement("CREATE TYPE statut_mouvement_wallet AS ENUM ('en_attente', 'valide', 'annule', 'echoue')");
        DB::statement("CREATE TYPE sens_mouvement_wallet AS ENUM ('credit', 'debit')");
        DB::statement("CREATE TYPE type_mouvement_wallet AS ENUM ('credit_commande', 'debit_retrait', 'remboursement', 'ajustement_credit', 'ajustement_debit')");
        DB::statement("CREATE TYPE statut_avis AS ENUM ('publie', 'masque')");
        DB::statement("CREATE TYPE canal_notification AS ENUM ('in_app', 'sms', 'email')");
    }

    public function down(): void
    {
        DB::statement('DROP TYPE IF EXISTS canal_notification');
        DB::statement('DROP TYPE IF EXISTS statut_avis');
        DB::statement('DROP TYPE IF EXISTS type_mouvement_wallet');
        DB::statement('DROP TYPE IF EXISTS sens_mouvement_wallet');
        DB::statement('DROP TYPE IF EXISTS statut_mouvement_wallet');
        DB::statement('DROP TYPE IF EXISTS statut_retrait');
        DB::statement('DROP TYPE IF EXISTS decision_litige');
        DB::statement('DROP TYPE IF EXISTS statut_litige');
        DB::statement('DROP TYPE IF EXISTS statut_revision');
        DB::statement('DROP TYPE IF EXISTS statut_livraison');
        DB::statement('DROP TYPE IF EXISTS statut_paiement');
        DB::statement('DROP TYPE IF EXISTS statut_cycle_commande');
        DB::statement('DROP TYPE IF EXISTS statut_annonce');
        DB::statement('DROP TYPE IF EXISTS statut_compte');
        DB::statement('DROP TYPE IF EXISTS role_utilisateur');
    }
};