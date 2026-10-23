<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Concerns\CadastroAuxiliar;
use Database\Factories\ResponsavelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('responsaveis')]
#[Fillable(['nome', 'email', 'telefone', 'setor', 'user_id', 'ativo'])]
class Responsavel extends Model
{
    /** @use HasFactory<ResponsavelFactory> */
    use Auditavel, CadastroAuxiliar, HasFactory;

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ativos(): HasMany
    {
        return $this->hasMany(Ativo::class);
    }

    protected function colunasBusca(): array
    {
        return ['nome', 'email', 'setor'];
    }
}
