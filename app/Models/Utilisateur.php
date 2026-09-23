<?php

namespace App\Models;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'utilisateurs';

    protected $primaryKey = 'id_utilisateur';

    protected $fillable = [
        'telephone',
        'mot_de_passe_hash',
        'nom',
        'prenom',
        'postnom',
        'email',
        'role',
        'image_profil',
        'statut_compte',
        'telephone_verifie_at',
        'derniere_connexion_at',
    ];

    protected $hidden = [
        'mot_de_passe_hash',
    ];

    protected function casts(): array
    {
        return [
            'role' => RoleUtilisateur::class,
            'statut_compte' => StatutCompte::class,
            'telephone_verifie_at' => 'datetime',
            'derniere_connexion_at' => 'datetime',
            'mot_de_passe_hash' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->mot_de_passe_hash;
    }
}