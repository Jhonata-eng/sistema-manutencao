<?php

namespace App\Http\Requests;

use App\Enums\TipoAnexo;
use App\Http\Requests\Concerns\NormalizaDinheiro;
use App\Http\Requests\Concerns\ValidaAnexo;
use App\Models\Manutencao;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Motivo e comprovante obrigatórios: um anexo nota/recibo já existente OU um arquivo novo. */
class CorrigirValorRequest extends FormRequest
{
    use NormalizaDinheiro, ValidaAnexo;

    public function authorize(): bool
    {
        return $this->user()->can('corrigirValor', $this->manutencao());
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarDinheiro('valor_novo', 'anexo_valor_nota');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Quem pode corrigir valor pode enviar o comprovante da correção, mesmo sem anexos.enviar.
        $regras = $this->regrasAnexo(false, TipoAnexo::comprovantes(), exigePermissaoEnvio: false);
        array_push($regras['anexo_arquivo'], 'required_without:comprovante_id', 'prohibits:comprovante_id');

        return [
            'valor_novo' => ['required', 'numeric', 'min:0', 'max:9999999999.99', function (string $atributo, mixed $valor, Closure $falhar) {
                if ((string) $valor === $this->manutencao()->valor_real) {
                    $falhar('O novo valor é igual ao valor atual.');
                }
            }],
            'motivo' => ['required', 'string', 'max:1000'],
            'comprovante_id' => [
                'nullable', 'required_without:anexo_arquivo', 'integer',
                Rule::exists('anexos', 'id')
                    ->where('manutencao_id', $this->manutencao()->id)
                    ->whereIn('tipo', array_map(fn (TipoAnexo $tipo) => $tipo->value, TipoAnexo::comprovantes())),
            ],
            ...$regras,
        ];
    }

    public function messages(): array
    {
        return [
            'comprovante_id.required_without' => 'Escolha uma nota/recibo já anexado ou envie o comprovante.',
            'anexo_arquivo.required_without' => 'Escolha uma nota/recibo já anexado ou envie o comprovante.',
            'anexo_arquivo.prohibits' => 'Escolha um comprovante já anexado ou envie um novo, não os dois.',
        ];
    }

    private function manutencao(): Manutencao
    {
        return $this->route('manutencao');
    }
}
