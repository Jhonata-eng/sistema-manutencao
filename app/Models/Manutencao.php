<?php

namespace App\Models;

use App\Enums\StatusManutencao;
use App\Enums\TipoAnexo;
use App\Enums\TipoManutencao;
use Database\Factories\ManutencaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Sem o trait Auditavel: cada ação (execução, cancelamento, reagendamento, correção, anexos)
 * registra o próprio evento na auditoria, e as ocorrências geradas pelo sistema não são auditadas uma a uma.
 */
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

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    /** chaperone: cada anexo carregado já conhece a manutenção (a AnexoPolicy a consulta). */
    public function anexos(): HasMany
    {
        return $this->hasMany(Anexo::class)->chaperone('manutencao');
    }

    public function correcoesValor(): HasMany
    {
        return $this->hasMany(CorrecaoValor::class);
    }

    /** Eventos de auditoria desta manutenção (exibidos na tela da manutenção). */
    public function eventos(): MorphMany
    {
        return $this->morphMany(Auditoria::class, 'auditavel');
    }

    /** Serviço exibido em listas: o executado, ou o do plano enquanto prevista. */
    public function servico(): string
    {
        return $this->descricao ?? $this->plano?->descricao ?? '—';
    }

    /** Realizada sem nota fiscal nem recibo anexado. Use com anexos carregados em listas. */
    public function semComprovante(): bool
    {
        return $this->status === StatusManutencao::Realizada
            && ! $this->anexos->contains(fn (Anexo $anexo) => $anexo->eComprovante());
    }

    public function estaAtrasada(): bool
    {
        return $this->status === StatusManutencao::Prevista && $this->data_prevista?->lt(today());
    }

    /** Escopo de dados pelas cidades do usuário, incluindo ativos excluídos (o gasto continua valendo). */
    #[Scope]
    protected function visivelPara(Builder $query, User $usuario): void
    {
        if (! $usuario->veTodasCidades()) {
            $query->whereIn('ativo_id', Ativo::withTrashed()->visivelPara($usuario)->select('id'));
        }
    }

    #[Scope]
    protected function semComprovanteAnexado(Builder $query): void
    {
        $query->where('status', StatusManutencao::Realizada)
            ->whereDoesntHave('anexos', fn (Builder $q) => $q->whereIn('tipo', TipoAnexo::comprovantes()));
    }

    /** Ainda por executar (inclui as atrasadas). */
    #[Scope]
    protected function pendentes(Builder $query): void
    {
        $query->where('status', StatusManutencao::Prevista);
    }

    /** Histórico: tudo menos as pendentes futuras (realizadas, canceladas e atrasadas). */
    #[Scope]
    protected function historico(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('status', '!=', StatusManutencao::Prevista)
            ->orWhere('data_prevista', '<', today()->toDateString()));
    }

    /** Pendentes com data prevista de hoje em diante. */
    #[Scope]
    protected function pendentesFuturas(Builder $query): void
    {
        $query->where('status', StatusManutencao::Prevista)->where('data_prevista', '>=', today()->toDateString());
    }
}
