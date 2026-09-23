<?php

namespace App\Models;

use App\Enums\SensMouvementWallet;
use App\Enums\StatutMouvementWallet;
use App\Enums\TypeMouvementWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementWallet extends Model
{
    protected $table = 'mouvements_wallet';

    protected $primaryKey = 'id_mouvement';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_wallet',
        'type_mouvement',
        'sens',
        'montant',
        'solde_avant',
        'solde_apres',
        'reference',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'type_mouvement' => TypeMouvementWallet::class,
            'sens' => SensMouvementWallet::class,
            'montant' => 'decimal:2',
            'solde_avant' => 'decimal:2',
            'solde_apres' => 'decimal:2',
            'statut' => StatutMouvementWallet::class,
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'id_wallet', 'id_wallet');
    }
}