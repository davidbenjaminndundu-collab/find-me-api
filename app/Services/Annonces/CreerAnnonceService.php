<?php

namespace App\Services\Annonces;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCompte;
use App\Models\Annonce;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CreerAnnonceService
{
    public function creer(Utilisateur $utilisateur, array $donnees): Annonce
    {
        if (
            $utilisateur->role !== RoleUtilisateur::Prestataire
            || $utilisateur->statut_compte !== StatutCompte::Actif
        ) {
            throw new HttpException(403, 'Seuls les prestataires actifs peuvent creer une annonce.');
        }

        $profil = ProfilPrestataire::query()
            ->where('id_utilisateur', $utilisateur->id_utilisateur)
            ->first();

        if ($profil === null) {
            throw new HttpException(403, 'Profil prestataire requis.');
        }

        return Annonce::query()->create([
            'id_categorie' => $donnees['id_categorie'],
            'id_prestataire' => $profil->id_prestataire,
            'titre' => $donnees['titre'],
            'description' => $donnees['description'],
            'prix_base' => $donnees['prix_base'],
            'delai_livraison_jours' => $donnees['delai_livraison_jours'],
            'nombre_revisions' => $donnees['nombre_revisions'] ?? 0,
            'livrables_inclus' => $donnees['livrables_inclus'],
            'elements_requis_client' => $donnees['elements_requis_client'],
            'image_couverture' => $donnees['image_couverture'] ?? null,
            'mots_cles' => $donnees['mots_cles'] ?? null,
            'conditions_particulieres' => $donnees['conditions_particulieres'] ?? null,
            'statut' => StatutAnnonce::Brouillon,
        ]);
    }
}