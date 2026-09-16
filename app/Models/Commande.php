<?php

namespace App\Models;

use App\Enums\StatutCommande;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commande extends Model
{
    protected $table = 'commandes';

    protected $primaryKey = 'id_commande';

    protected $fillable = [
        'id_prestataire',
        'id_client',
        'id_annonce',
        'reference_commande',
        'description_commande',
        'titre_service',
        'est_offre_personnalisee',
        'montant_total',
        'taux_commission',
        'montant_commission',
        'montant_prestataire',
        'delai_livraison_jours',
        'nombre_revisions_incluses',
        'nombre_revisions_utilisees',
        'livrables_convenus',
        'statut',
        'date_acceptation',
        'date_expiration_offre',
        'date_paiement',
        'date_limite_livraison',
        'date_livraison',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'est_offre_personnalisee' => 'boolean',
            'montant_total' => 'decimal:2',
            'taux_commission' => 'decimal:2',
            'montant_commission' => 'decimal:2',
            'montant_prestataire' => 'decimal:2',
            'delai_livraison_jours' => 'integer',
            'nombre_revisions_incluses' => 'integer',
            'nombre_revisions_utilisees' => 'integer',
            'statut' => StatutCommande::class,
            'date_acceptation' => 'datetime',
            'date_expiration_offre' => 'datetime',
            'date_paiement' => 'datetime',
            'date_limite_livraison' => 'datetime',
            'date_livraison' => 'datetime',
            'date_fin' => 'datetime',
        ];
    }

    public function prestataire(): BelongsTo
    {
        return $this->belongsTo(ProfilPrestataire::class, 'id_prestataire', 'id_prestataire');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_client', 'id_utilisateur');
    }

    public function annonce(): BelongsTo
    {
        return $this->belongsTo(Annonce::class, 'id_annonce', 'id_annonce');
    }
}