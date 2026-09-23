<?php

namespace App\Models;

use App\Enums\CanalNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $primaryKey = 'id_notification';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_utilisateur',
        'type_notification',
        'titre',
        'message',
        'canal',
        'lien',
        'lu_at',
    ];

    protected function casts(): array
    {
        return [
            'canal' => CanalNotification::class,
            'lu_at' => 'datetime',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }
}