<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Concerns\CadastroAuxiliar;
use App\Support\Documento;
use Database\Factories\FornecedorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('fornecedores')]
#[Fillable(['nome', 'documento', 'telefone', 'email', 'ativo'])]
class Fornecedor extends Model
{
    /** @use HasFactory<FornecedorFactory> */
    use Auditavel, CadastroAuxiliar, HasFactory;

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    /** CPF ou CNPJ com máscara, para exibição. */
    protected function documentoFormatado(): Attribute
    {
        return Attribute::get(fn () => Documento::formatar($this->documento));
    }

    protected function colunasBusca(): array
    {
        return ['nome', 'documento', 'email'];
    }

    protected function termoBusca(string $coluna, string $busca): string
    {
        return $coluna === 'documento' ? (Documento::normalizar($busca) ?? $busca) : $busca;
    }
}
