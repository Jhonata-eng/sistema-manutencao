<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaDinheiro;
use App\Models\Fornecedor;
use App\Models\PlanoManutencao;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarPlanoRequest extends FormRequest
{
    use NormalizaDinheiro;

    public function authorize(): bool
    {
        return $this->plano()
            ? $this->user()->can('update', $this->plano())
            : $this->user()->can('create', [PlanoManutencao::class, $this->route('ativo')]);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarDinheiro('valor_estimado');

        // O select traz o número de meses das periodicidades com nome; "personalizada" usa o campo numérico.
        if ($this->input('periodicidade') !== 'personalizada') {
            $this->merge(['periodicidade_meses' => $this->input('periodicidade')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $plano = $this->plano();

        return [
            'descricao' => ['required', 'string', 'max:150'],
            'periodicidade' => ['required', Rule::in([...array_keys(PlanoManutencao::PERIODICIDADES), 'personalizada'])],
            'periodicidade_meses' => ['required', 'integer', 'min:1', 'max:'.PlanoManutencao::PERIODICIDADE_MAXIMA],
            // No cadastro, a primeira ocorrência é hoje ou depois; na edição, o início pode estar no passado.
            'data_inicio' => ['required', 'date', ...($plano ? [] : ['after_or_equal:today'])],
            'data_termino' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'valor_estimado' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'fornecedor_id' => ['nullable', 'integer', Fornecedor::regraSelecionavel($plano?->fornecedor_id)],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['required', 'boolean', function (string $atributo, mixed $valor, Closure $falhar) {
                $equipamento = $this->plano()?->equipamento ?? $this->route('ativo');

                if ($valor && ($equipamento->trashed() || ! $equipamento->emOperacao())) {
                    $falhar('O ativo não está em operação, então o plano não pode ficar ativo.');
                }
            }],
            'confirmar_cancelamento' => [Rule::when($this->vaiCancelarPendentes(), ['accepted'], ['nullable'])],
        ];
    }

    public function messages(): array
    {
        return [
            'data_inicio.after_or_equal' => 'A primeira ocorrência deve ser hoje ou uma data futura.',
            'data_termino.after_or_equal' => 'A data de término deve ser igual ou posterior à primeira ocorrência.',
            'confirmar_cancelamento.accepted' => 'Confirme o cancelamento das manutenções pendentes para inativar o plano.',
        ];
    }

    /** Somente os campos do plano (sem os auxiliares do formulário). */
    public function dados(): array
    {
        return $this->safe()->except(['periodicidade', 'confirmar_cancelamento']);
    }

    private function plano(): ?PlanoManutencao
    {
        return $this->route('plano');
    }

    private function vaiCancelarPendentes(): bool
    {
        $plano = $this->plano();

        return $plano && $plano->ativo && ! $this->boolean('ativo') && $plano->manutencoes()->pendentes()->exists();
    }
}
