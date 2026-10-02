<?php

namespace App\Services\Commandes;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\StatutCompte;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CreerDemandeService
{
    private const TAUX_COMMISSION = '10.00';

    public function creer(
        Utilisateur $utilisateur,
        array $donnees
    ): Commande {
        $this->verifierClient($utilisateur);

        $annonce = Annonce::query()
            ->where('id_annonce', $donnees['id_annonce'])
            ->where('statut', StatutAnnonce::Publiee)
            ->first();

        if ($annonce === null) {
            throw new HttpException(
                404,
                'Annonce publiée introuvable.'
            );
        }

        /*
         * On fige ici les conditions de l'annonce.
         *
         * Si le prestataire modifie son annonce plus tard,
         * cette commande doit conserver les conditions initiales.
         */
        $montantTotal = (string) $annonce->prix_base;

        $montantCommission = bcmul(
            $montantTotal,
            '0.10',
            2
        );

        $montantPrestataire = bcsub(
            $montantTotal,
            $montantCommission,
            2
        );

        return DB::transaction(function () use (
            $utilisateur,
            $donnees,
            $annonce,
            $montantTotal,
            $montantCommission,
            $montantPrestataire
        ): Commande {
            return Commande::query()->create([
                'id_prestataire' =>
                    $annonce->id_prestataire,

                'id_client' =>
                    $utilisateur->id_utilisateur,

                'id_annonce' =>
                    $annonce->id_annonce,

                'reference_commande' =>
                    $this->genererReference(),

                'description_commande' =>
                    $donnees['description_commande'],

                'titre_service' =>
                    $annonce->titre,

                'est_offre_personnalisee' =>
                    false,

                'montant_total' =>
                    $montantTotal,

                'taux_commission' =>
                    self::TAUX_COMMISSION,

                'montant_commission' =>
                    $montantCommission,

                'montant_prestataire' =>
                    $montantPrestataire,

                'delai_livraison_jours' =>
                    $annonce->delai_livraison_jours,

                'nombre_revisions_incluses' =>
                    $annonce->nombre_revisions,

                'nombre_revisions_utilisees' =>
                    0,

                'livrables_convenus' =>
                    $annonce->livrables_inclus,

                'statut' =>
                    StatutCommande::EnAttenteReponse,
            ]);
        });
    }

    private function verifierClient(
        Utilisateur $utilisateur
    ): void {
        if (
            $utilisateur->role !== RoleUtilisateur::Client
            || $utilisateur->statut_compte !== StatutCompte::Actif
        ) {
            throw new HttpException(
                403,
                'Seuls les clients actifs peuvent envoyer une demande.'
            );
        }
    }

    private function genererReference(): string
    {
        do {
            $reference =
                'CMD-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(Str::random(8));
        } while (
            Commande::query()
                ->where('reference_commande', $reference)
                ->exists()
        );

        return $reference;
    }
}