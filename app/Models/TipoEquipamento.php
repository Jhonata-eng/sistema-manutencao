<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Concerns\CadastroAuxiliar;
use Database\Factories\TipoEquipamentoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('tipos_equipamento')]
#[Fillable(['nome', 'ativo'])]
class TipoEquipamento extends Model
{
    /** @use HasFactory<TipoEquipamentoFactory> */
    use Auditavel, CadastroAuxiliar, HasFactory;

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function ativos(): HasMany
    {
        return $this->hasMany(Ativo::class);
    }

    protected function colunasBusca(): array
    {
        return ['nome'];
    }
}
