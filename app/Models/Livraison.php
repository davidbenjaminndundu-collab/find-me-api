<?php

namespace App\Models;

use App\Enums\StatutLivraison;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Livraison extends Model
{
    protected $table = 'livraisons';

    protected $primaryKey = 'id_livraison';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_commande',
        'numero_version',
        'message',
        'url_fichier',
        'nom_fichier',
        'type_mime',
        'taille_octets',
        'statut',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'numero_version' => 'integer',
            'taille_octets' => 'integer',
            'statut' => StatutLivraison::class,
            'delivered_at' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }
}