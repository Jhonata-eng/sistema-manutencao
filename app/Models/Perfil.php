<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Database\Factories\PerfilFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('perfis')]
#[Fillable(['nome', 'descricao'])]
class Perfil extends Model
{
    /** @use HasFactory<PerfilFactory> */
    use Auditavel, HasFactory;

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'perfil_permissao');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
