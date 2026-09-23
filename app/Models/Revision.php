<?php

namespace App\Models;

use App\Enums\StatutRevision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revision extends Model
{
    protected $table = 'revisions';

    protected $primaryKey = 'id_revision';

    protected $fillable = [
        'id_commande',
        'description',
        'statut',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutRevision::class,
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }
}