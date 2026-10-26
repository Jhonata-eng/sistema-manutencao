<?php

namespace App\Http\Requests;

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Services\FiltroManutencoes;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filtros da visão unificada, do calendário, da exportação e do painel (todos opcionais). */
class FiltrarManutencoesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
            'mes' => ['nullable', 'date_format:Y-m'],
            'data' => ['nullable', Rule::in(array_keys(FiltroManutencoes::BASES_DE_DATA))],
            'status' => ['nullable', Rule::in([...array_column(StatusManutencao::cases(), 'value'), 'atrasada'])],
            'cidade' => ['nullable', 'integer'],
            'tipo_equipamento' => ['nullable', 'integer'],
            'responsavel' => ['nullable', 'integer'],
            'ativo' => ['nullable', 'string', 'max:50'],
            'ativo_id' => ['nullable', 'integer'],
            'fornecedor' => ['nullable', 'integer'],
            'tipo' => ['nullable', Rule::enum(TipoManutencao::class)],
            'sem_comprovante' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Filtros validados no formato do FiltroManutencoes. Sem nenhum parâmetro na URL, a lista abre
     * no mês atual ($mesAtualPorPadrao); "mes=AAAA-MM" (clique num gráfico) vira o período do mês.
     */
    public function filtros(bool $mesAtualPorPadrao = false): array
    {
        $filtros = array_filter($this->safe()->only(FiltroManutencoes::CHAVES), fn ($valor) => $valor !== null && $valor !== '');
        $mes = $this->validated('mes') ?? ($mesAtualPorPadrao && $this->query() === [] ? today()->format('Y-m') : null);

        if ($mes) {
            $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes);
            $filtros['de'] = $inicio->toDateString();
            $filtros['ate'] = $inicio->endOfMonth()->toDateString();
        }

        return $filtros;
    }
}
