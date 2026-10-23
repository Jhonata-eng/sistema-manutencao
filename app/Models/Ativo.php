<?php

namespace App\Models;

use App\Enums\StatusAtivo;
use App\Models\Concerns\Auditavel;
use Database\Factories\AtivoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tipo_equipamento_id', 'marca', 'modelo', 'numero_serie', 'ano_fabricacao', 'patrimonio', 'capacidade',
    'cidade_id', 'localizacao', 'responsavel_id', 'data_aquisicao', 'status', 'observacoes',
])]
class Ativo extends Model
{
    /** @use HasFactory<AtivoFactory> */
    use Auditavel, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => StatusAtivo::class,
            'ano_fabricacao' => 'integer',
            'data_aquisicao' => 'date',
        ];
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipamento::class, 'tipo_equipamento_id');
    }

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Responsavel::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    /** Escopo de dados: só ativos das cidades do usuário, salvo dados.ver_todas_cidades. */
    #[Scope]
    protected function visivelPara(Builder $query, User $usuario): void
    {
        if (! $usuario->veTodasCidades()) {
            $query->whereIn('cidade_id', $usuario->cidades()->select('cidades.id'));
        }
    }

    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['busca'] ?? null, fn (Builder $q, string $busca) => $q->where(
                fn (Builder $q) => $q->whereLike('patrimonio', "%{$busca}%")
                    ->orWhereLike('marca', "%{$busca}%")
                    ->orWhereLike('modelo', "%{$busca}%")
                    ->orWhereLike('numero_serie', "%{$busca}%")
                    ->orWhereLike('localizacao', "%{$busca}%")
            ))
            ->when($filtros['cidade'] ?? null, fn (Builder $q, $cidade) => $q->where('cidade_id', $cidade))
            ->when($filtros['tipo'] ?? null, fn (Builder $q, $tipo) => $q->where('tipo_equipamento_id', $tipo))
            ->when($filtros['responsavel'] ?? null, fn (Builder $q, $responsavel) => $q->where('responsavel_id', $responsavel))
            ->when($filtros['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status));
    }
}
