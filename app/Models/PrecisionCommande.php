<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecisionCommande extends Model
{
    protected $table = 'precisions_commandes';

    protected $primaryKey = 'id_precision';

    protected $fillable = [
        'id_commande',
        'demande',
        'reponse',
        'demande_at',
        'reponse_at',
    ];

    protected function casts(): array
    {
        return [
            'demande_at' => 'datetime',
            'reponse_at' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(
            Commande::class,
            'id_commande',
            'id_commande'
        );
    }
}