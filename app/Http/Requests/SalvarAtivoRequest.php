<?php

namespace App\Http\Requests;

use App\Enums\StatusAtivo;
use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\Responsavel;
use App\Models\TipoEquipamento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarAtivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ativo = $this->route('ativo');

        return $ativo ? $this->user()->can('update', $ativo) : $this->user()->can('create', Ativo::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Ativo|null $ativo */
        $ativo = $this->route('ativo');

        return [
            'tipo_equipamento_id' => ['required', 'integer', TipoEquipamento::regraSelecionavel($ativo?->tipo_equipamento_id)],
            'marca' => ['required', 'string', 'max:60'],
            'modelo' => ['required', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'ano_fabricacao' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            // A regra unique consulta a tabela inteira, inclusive ativos excluídos logicamente.
            'patrimonio' => ['required', 'string', 'max:50', Rule::unique('ativos')->ignore($ativo)],
            'capacidade' => ['nullable', 'string', 'max:100'],
            'cidade_id' => [
                'required', 'integer', Cidade::regraSelecionavel($ativo?->cidade_id),
                function (string $atributo, mixed $valor, Closure $falhar) {
                    if (! $this->user()->podeAcessarCidade((int) $valor)) {
                        $falhar('Você não tem acesso a esta cidade.');
                    }
                },
            ],
            'localizacao' => ['required', 'string', 'max:150'],
            'responsavel_id' => ['nullable', 'integer', Responsavel::regraSelecionavel($ativo?->responsavel_id)],
            'data_aquisicao' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::enum(StatusAtivo::class)],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remover_foto' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['patrimonio.unique' => 'Este número de patrimônio já está em uso (inclusive por ativo excluído).'];
    }
}
