<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * Exclui cadastros que ninguém referencia. As chaves estrangeiras com RESTRICT são a fonte
 * da verdade de "em uso" (inclusive ativos excluídos logicamente), então novas tabelas que
 * apontem para um cadastro passam a bloquear a exclusão sem mudar este código.
 */
class ExclusaoProtegida
{
    /** Código de erro do MySQL/MariaDB para FK que impede apagar a linha referenciada. */
    private const ERRO_EM_USO = 1451;

    /** @param  string  $descricao  ex.: "a cidade Campinas/SP" */
    public function excluir(Model $registro, string $descricao): void
    {
        try {
            $registro->delete();
        } catch (QueryException $erro) {
            if (($erro->errorInfo[1] ?? null) !== self::ERRO_EM_USO) {
                throw $erro;
            }

            throw ValidationException::withMessages([
                'protecao' => "Não é possível excluir {$descricao}: há registros vinculados. Inative o cadastro para que ele não seja mais usado.",
            ]);
        }
    }
}
