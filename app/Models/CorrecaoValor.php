<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Histórico de correções do valor real; a auditoria (valor_corrigido) fica na manutenção. */
#[Table('correcoes_valor')]
#[Fillable(['valor_anterior', 'valor_novo', 'motivo', 'anexo_id', 'user_id'])]
class CorrecaoValor extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'valor_anterior' => 'decimal:2',
            'valor_novo' => 'decimal:2',
        ];
    }

    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
