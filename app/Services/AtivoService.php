<?php

namespace App\Services;

use App\Models\Ativo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AtivoService
{
    /** Pasta das fotos no disco privado (storage/app/private). */
    private const PASTA_FOTOS = 'ativos/fotos';

    /**
     * Cria (instância nova) ou atualiza um ativo. A foto nova substitui a anterior;
     * $removerFoto apaga a atual sem enviar outra.
     */
    public function salvar(Ativo $ativo, array $dados, User $autor, ?UploadedFile $foto = null, bool $removerFoto = false): Ativo
    {
        $ativo->fill($dados);

        if (! $ativo->exists) {
            $ativo->criado_por = $autor->id;
        }

        $fotoAnterior = $ativo->foto_path;

        if ($foto) {
            $ativo->foto_path = $foto->store(self::PASTA_FOTOS, 'local');
        } elseif ($removerFoto) {
            $ativo->foto_path = null;
        }

        try {
            $ativo->save();
        } catch (Throwable $erro) {
            if ($foto) {
                Storage::disk('local')->delete($ativo->foto_path);
            }

            throw $erro;
        }

        if ($fotoAnterior && $fotoAnterior !== $ativo->foto_path) {
            Storage::disk('local')->delete($fotoAnterior);
        }

        return $ativo;
    }

    /** Exclusão lógica; o histórico e os gastos continuam nos relatórios. */
    public function excluir(Ativo $ativo): void
    {
        $ativo->delete();
    }
}
