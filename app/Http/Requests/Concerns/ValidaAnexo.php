<?php

namespace App\Http\Requests\Concerns;

use App\Enums\TipoAnexo;
use Illuminate\Validation\Rule;

/**
 * Campos anexo_* (arquivo e metadados) usados no registro de execução, na não programada, no envio
 * avulso e na correção de valor. O tipo do arquivo é conferido pelo conteúdo (MIME real), não pelo nome.
 * A classe que usa este trait precisa usar também NormalizaDinheiro.
 */
trait ValidaAnexo
{
    private const MIMES_ANEXO = ['application/pdf', 'image/jpeg', 'image/png', 'text/xml', 'application/xml'];

    /**
     * @param  list<TipoAnexo>|null  $tipos  tipos aceitos (todos, se null)
     * @param  bool  $exigePermissaoEnvio  sem anexos.enviar, o arquivo é proibido
     */
    protected function regrasAnexo(bool $obrigatorio, ?array $tipos = null, bool $exigePermissaoEnvio = true): array
    {
        $tamanhoMaximoKb = config('manutencao.anexo_tamanho_max_mb') * 1024;

        return [
            'anexo_arquivo' => [
                $obrigatorio ? 'required' : 'nullable',
                Rule::prohibitedIf($exigePermissaoEnvio && ! $this->user()->can('anexos.enviar')),
                'file',
                'extensions:pdf,jpg,jpeg,png,xml',
                'mimetypes:'.implode(',', self::MIMES_ANEXO),
                "max:{$tamanhoMaximoKb}",
            ],
            'anexo_tipo' => ['nullable', 'required_with:anexo_arquivo', Rule::in(array_keys(TipoAnexo::opcoes($tipos)))],
            'anexo_numero_nota' => ['nullable', 'string', 'max:50'],
            'anexo_data_emissao' => ['nullable', 'date', 'before_or_equal:today'],
            'anexo_valor_nota' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    /** Arquivo e metadados validados, no formato do AnexoService, ou null se nenhum arquivo foi enviado. */
    public function anexo(): ?array
    {
        if (! $this->hasFile('anexo_arquivo')) {
            return null;
        }

        return [
            'arquivo' => $this->file('anexo_arquivo'),
            'tipo' => $this->validated('anexo_tipo'),
            'numero_nota' => $this->validated('anexo_numero_nota'),
            'data_emissao' => $this->validated('anexo_data_emissao'),
            'valor_nota' => $this->validated('anexo_valor_nota'),
        ];
    }
}
