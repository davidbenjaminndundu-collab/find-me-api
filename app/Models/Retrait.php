<?php

namespace App\Models;

use App\Enums\StatutRetrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Retrait extends Model
{
    protected $table = 'retraits';

    protected $primaryKey = 'id_retrait';

    protected $fillable = [
        'id_wallet',
        'reference_retrait',
        'montant',
        'frais',
        'montant_net',
        'operateur',
        'numero_mobile_money',
        'reference_operateur',
        'statut',
        'motif_refus',
        'requested_at',
        'approved_at',
        'executed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'frais' => 'decimal:2',
            'montant_net' => 'decimal:2',
            'statut' => StatutRetrait::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'executed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'id_wallet', 'id_wallet');
    }
}