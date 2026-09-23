<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $table = 'wallets';

    protected $primaryKey = 'id_wallet';

    protected $fillable = [
        'id_prestataire',
        'devise',
        'solde_en_attente',
        'solde_disponible',
    ];

    protected function casts(): array
    {
        return [
            'solde_en_attente' => 'decimal:2',
            'solde_disponible' => 'decimal:2',
        ];
    }

    public function prestataire(): BelongsTo
    {
        return $this->belongsTo(ProfilPrestataire::class, 'id_prestataire', 'id_prestataire');
    }
}