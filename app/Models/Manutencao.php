<?php

namespace App\Models;

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use Database\Factories\ManutencaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('manutencoes')]
#[Fillable([
    'tipo', 'data_referencia', 'data_prevista', 'data_execucao', 'status', 'valor_previsto', 'valor_real',
    'fornecedor_id', 'descricao', 'observacoes',
])]
class Manutencao extends Model
{
    /** @use HasFactory<ManutencaoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoManutencao::class,
            'status' => StatusManutencao::class,
            'data_referencia' => 'immutable_date',
            'data_prevista' => 'immutable_date',
            'data_execucao' => 'immutable_date',
            'valor_previsto' => 'decimal:2',
            'valor_real' => 'decimal:2',
            'cancelado_em' => 'datetime',
        ];
    }

    public function ativo(): BelongsTo
    {
        return $this->belongsTo(Ativo::class)->withTrashed();
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoManutencao::class, 'plano_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function estaAtrasada(): bool
    {
        return $this->status === StatusManutencao::Prevista && $this->data_prevista?->lt(today());
    }

    /** Ainda por executar (inclui as atrasadas). */
    #[Scope]
    protected function pendentes(Builder $query): void
    {
        $query->where('status', StatusManutencao::Prevista);
    }

    /** Pendentes com data prevista de hoje em diante. */
    #[Scope]
    protected function pendentesFuturas(Builder $query): void
    {
        $query->where('status', StatusManutencao::Prevista)->where('data_prevista', '>=', today()->toDateString());
    }
}
