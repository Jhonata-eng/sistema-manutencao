<?php

namespace App\Models;

use App\Enums\StatusManutencao;
use App\Models\Concerns\Auditavel;
use Database\Factories\PlanoManutencaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('planos_manutencao')]
#[Fillable([
    'descricao', 'periodicidade_meses', 'data_inicio', 'data_termino', 'valor_estimado',
    'fornecedor_id', 'observacoes', 'ativo',
])]
class PlanoManutencao extends Model
{
    /** @use HasFactory<PlanoManutencaoFactory> */
    use Auditavel, HasFactory;

    /** Periodicidades com nome; qualquer outro número de meses é "personalizada". */
    public const PERIODICIDADES = [1 => 'Mensal', 2 => 'Bimestral', 3 => 'Trimestral', 6 => 'Semestral', 12 => 'Anual'];

    public const PERIODICIDADE_MAXIMA = 120;

    protected function casts(): array
    {
        return [
            'periodicidade_meses' => 'integer',
            'data_inicio' => 'immutable_date',
            'data_termino' => 'immutable_date',
            'valor_estimado' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    /** O ativo do plano. Não se chama ativo() porque `ativo` é a coluna booleana de situação. */
    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Ativo::class, 'ativo_id')->withTrashed();
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function manutencoes(): HasMany
    {
        return $this->hasMany(Manutencao::class, 'plano_id');
    }

    public function rotuloPeriodicidade(): string
    {
        return self::PERIODICIDADES[$this->periodicidade_meses] ?? "A cada {$this->periodicidade_meses} meses";
    }

    /** Média do valor real das execuções, com 2 casas (string decimal), ou null se nunca foi executado. */
    public function mediaReal(): ?string
    {
        return $this->manutencoes()
            ->where('status', StatusManutencao::Realizada)
            ->selectRaw('ROUND(AVG(valor_real), 2) as media')
            ->value('media');
    }
}
