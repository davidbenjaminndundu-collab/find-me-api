<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallbackPaiement extends Model
{
    protected $table = 'callbacks_paiement';

    protected $primaryKey = 'id_callback';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_paiement',
        'operateur',
        'identifiant_evenement',
        'signature_valide',
        'statut_recu',
        'montant_recu',
        'reference_operateur',
        'payload',
        'resultat_traitement',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_valide' => 'boolean',
            'montant_recu' => 'decimal:2',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'id_paiement', 'id_paiement');
    }
}