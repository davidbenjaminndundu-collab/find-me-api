<?php

namespace App\Models;

use App\Enums\StatutAnnonce;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Annonce extends Model
{
    protected $table = 'annonces';

    protected $primaryKey = 'id_annonce';

    protected $fillable = [
        'id_categorie',
        'id_prestataire',
        'titre',
        'description',
        'prix_base',
        'delai_livraison_jours',
        'nombre_revisions',
        'livrables_inclus',
        'elements_requis_client',
        'image_couverture',
        'mots_cles',
        'conditions_particulieres',
        'statut',
        'motif_refus',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'prix_base' => 'decimal:2',
            'delai_livraison_jours' => 'integer',
            'nombre_revisions' => 'integer',
            'statut' => StatutAnnonce::class,
            'published_at' => 'datetime',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'id_categorie', 'id_categorie');
    }

    public function prestataire(): BelongsTo
    {
        return $this->belongsTo(ProfilPrestataire::class, 'id_prestataire', 'id_prestataire');
    }
}