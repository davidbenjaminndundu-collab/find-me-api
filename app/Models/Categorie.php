<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    protected $table = 'categories';

    protected $primaryKey = 'id_categorie';

    protected $fillable = [
        'nom',
        'description',
        'icone',
        'est_active',
    ];

    protected function casts(): array
    {
        return [
            'est_active' => 'boolean',
        ];
    }
}