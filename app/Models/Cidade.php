<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Concerns\CadastroAuxiliar;
use Database\Factories\CidadeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'uf', 'ativo'])]
class Cidade extends Model
{
    /** @use HasFactory<CidadeFactory> */
    use Auditavel, CadastroAuxiliar, HasFactory;

    public const UFS = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA',
        'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO',
    ];

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

    public function ativos(): HasMany
    {
        return $this->hasMany(Ativo::class);
    }

    /** "Campinas/SP" */
    protected function nomeUf(): Attribute
    {
        return Attribute::get(fn () => "{$this->nome}/{$this->uf}");
    }

    protected function colunasBusca(): array
    {
        return ['nome', 'uf'];
    }
}
