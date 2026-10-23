<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Database\Factories\CidadeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nome', 'uf', 'ativo'])]
class Cidade extends Model
{
    /** @use HasFactory<CidadeFactory> */
    use Auditavel, HasFactory;

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** "Campinas/SP" */
    protected function nomeUf(): Attribute
    {
        return Attribute::get(fn () => "{$this->nome}/{$this->uf}");
    }
}
