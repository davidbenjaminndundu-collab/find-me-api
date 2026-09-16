<?php

namespace App\Models;

use App\Enums\DecisionLitige;
use App\Enums\StatutLitige;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Litige extends Model
{
    protected $table = 'litiges';

    protected $primaryKey = 'id_litige';

    protected $fillable = [
        'id_commande',
        'motif',
        'description',
        'preuves',
        'statut',
        'decision',
        'montant_client',
        'montant_prestataire',
        'opened_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'preuves' => 'array',
            'statut' => StatutLitige::class,
            'decision' => DecisionLitige::class,
            'montant_client' => 'decimal:2',
            'montant_prestataire' => 'decimal:2',
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }
}