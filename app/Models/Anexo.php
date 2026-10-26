<?php

namespace App\Models;

use App\Enums\TipoAnexo;
use Database\Factories\AnexoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Auditoria: os eventos anexo_enviado/anexo_excluido ficam na manutenção (AnexoService). */
#[Fillable(['tipo', 'numero_nota', 'data_emissao', 'valor_nota', 'nome_original', 'caminho', 'mime', 'tamanho'])]
class Anexo extends Model
{
    /** @use HasFactory<AnexoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoAnexo::class,
            'data_emissao' => 'immutable_date',
            'valor_nota' => 'decimal:2',
            'tamanho' => 'integer',
        ];
    }

    public function manutencao(): BelongsTo
    {
        return $this->belongsTo(Manutencao::class);
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function eComprovante(): bool
    {
        return in_array($this->tipo, TipoAnexo::comprovantes(), true);
    }

    /** Descrição curta para listas e para a auditoria: "Nota fiscal nº 123 (nota.pdf)". */
    public function descricao(): string
    {
        return $this->tipo->rotulo().($this->numero_nota ? " nº {$this->numero_nota}" : '')." ({$this->nome_original})";
    }
}
