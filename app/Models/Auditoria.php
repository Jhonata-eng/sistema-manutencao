<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Table('auditoria')]
#[Fillable(['user_id', 'evento', 'auditavel_type', 'auditavel_id', 'antes', 'depois', 'motivo', 'ip', 'user_agent'])]
class Auditoria extends Model
{
    public const UPDATED_AT = null;

    /**
     * Models auditáveis: alias gravado em auditavel_type => [classe, rótulo].
     * Também é o morph map da aplicação (AppServiceProvider).
     */
    public const TIPOS = [
        'usuario' => [User::class, 'Usuário'],
        'perfil' => [Perfil::class, 'Perfil'],
        'cidade' => [Cidade::class, 'Cidade'],
    ];

    public const EVENTOS = [
        'criado' => 'Criação',
        'editado' => 'Edição',
        'excluido' => 'Exclusão',
        'permissoes_alteradas' => 'Alteração de permissões',
        'cidades_alteradas' => 'Alteração de cidades vinculadas',
        'senha_alterada' => 'Troca da própria senha',
        'senha_redefinida' => 'Redefinição de senha',
    ];

    protected function casts(): array
    {
        return [
            'antes' => 'array',
            'depois' => 'array',
        ];
    }

    public static function registrar(string $evento, Model $modelo, array $antes = [], array $depois = [], ?string $motivo = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'evento' => $evento,
            'auditavel_type' => $modelo->getMorphClass(),
            'auditavel_id' => $modelo->getKey(),
            'antes' => $antes ?: null,
            'depois' => $depois ?: null,
            'motivo' => $motivo,
            'ip' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 250, ''),
        ]);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rotuloEvento(): string
    {
        return self::EVENTOS[$this->evento] ?? Str::ucfirst(str_replace('_', ' ', $this->evento));
    }

    public function rotuloTipo(): string
    {
        return self::TIPOS[$this->auditavel_type][1] ?? $this->auditavel_type;
    }

    /** Campos alterados com valores antes/depois, para a tela de detalhes. */
    public function diferencas(): array
    {
        $antes = $this->antes ?? [];
        $depois = $this->depois ?? [];

        return collect(array_keys($antes + $depois))
            ->mapWithKeys(fn (string $campo) => [$campo => [$antes[$campo] ?? null, $depois[$campo] ?? null]])
            ->all();
    }

    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['usuario'] ?? null, fn (Builder $q, $usuario) => $q->where('user_id', $usuario))
            ->when($filtros['evento'] ?? null, fn (Builder $q, string $evento) => $q->where('evento', $evento))
            ->when($filtros['tipo'] ?? null, fn (Builder $q, string $tipo) => $q->where('auditavel_type', $tipo))
            ->when($filtros['registro'] ?? null, fn (Builder $q, $id) => $q->where('auditavel_id', $id))
            ->when($filtros['de'] ?? null, fn (Builder $q, string $de) => $q->where('created_at', '>=', "{$de} 00:00:00"))
            ->when($filtros['ate'] ?? null, fn (Builder $q, string $ate) => $q->where('created_at', '<=', "{$ate} 23:59:59"));
    }
}
