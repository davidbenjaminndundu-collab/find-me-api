<?php

namespace App\Models;

use App\Enums\StatutDisponibilite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilPrestataire extends Model
{
    protected $table = 'profils_prestataires';

    protected $primaryKey = 'id_prestataire';

    protected $fillable = [
        'id_utilisateur',
        'nom_professionnel',
        'bio',
        'competences',
        'portfolio_url',
        'note_moyenne',
        'nombre_avis',
        'statut_disponibilite',
    ];

    protected function casts(): array
    {
        return [
            'note_moyenne' => 'decimal:2',
            'nombre_avis' => 'integer',
            'statut_disponibilite' => StatutDisponibilite::class,
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }
}