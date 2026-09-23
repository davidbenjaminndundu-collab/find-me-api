<?php

namespace App\Models;

use App\Enums\StatutPaiement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $table = 'paiements';

    protected $primaryKey = 'id_paiement';

    protected $fillable = [
        'id_commande',
        'reference_interne',
        'montant',
        'devise',
        'operateur',
        'numero_payeur_masque',
        'reference_operateur',
        'statut',
        'idempotency_key',
        'initiated_at',
        'confirmed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'statut' => StatutPaiement::class,
            'initiated_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }
}