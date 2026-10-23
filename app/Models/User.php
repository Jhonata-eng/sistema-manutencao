<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'perfil_id', 'ativo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditavel, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }

    public function cidades(): BelongsToMany
    {
        return $this->belongsToMany(Cidade::class);
    }

    /**
     * As permissões vêm do perfil; a relação fica carregada na instância,
     * então vários @can na mesma página custam duas consultas no total.
     */
    public function temPermissao(string $chave): bool
    {
        return $this->perfil->permissoes->contains('chave', $chave);
    }

    public function veTodasCidades(): bool
    {
        return $this->temPermissao('dados.ver_todas_cidades');
    }

    public function podeAcessarCidade(?int $cidadeId): bool
    {
        return $this->veTodasCidades() || $this->cidades->contains('id', $cidadeId);
    }

    /** Cidades que o usuário pode ver e operar (todas, com dados.ver_todas_cidades). */
    public function cidadesAcessiveis(): Builder
    {
        return Cidade::query()->when(
            ! $this->veTodasCidades(),
            fn (Builder $q) => $q->whereIn('id', $this->cidades()->select('cidades.id')),
        );
    }

    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['busca'] ?? null, fn (Builder $q, string $busca) => $q->where(
                fn (Builder $q) => $q->whereLike('name', "%{$busca}%")->orWhereLike('email', "%{$busca}%")
            ))
            ->when($filtros['perfil'] ?? null, fn (Builder $q, $perfil) => $q->where('perfil_id', $perfil))
            ->when($filtros['status'] ?? null, fn (Builder $q, string $status) => $q->where('ativo', $status === 'ativo'));
    }
}
