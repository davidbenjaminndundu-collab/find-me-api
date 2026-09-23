<?php

namespace App\Models;

use App\Enums\StatutAvis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avis extends Model
{
    protected $table = 'avis';

    protected $primaryKey = 'id_avis';

    protected $fillable = [
        'id_commande',
        'id_client',
        'note',
        'commentaire',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'note' => 'integer',
            'statut' => StatutAvis::class,
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_client', 'id_utilisateur');
    }
}