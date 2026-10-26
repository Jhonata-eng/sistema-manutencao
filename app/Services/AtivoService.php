<?php

namespace App\Services;

use App\Models\Ativo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AtivoService
{
    /** Pasta das fotos no disco privado (storage/app/private). */
    private const PASTA_FOTOS = 'ativos/fotos';

    public function __construct(private GeradorOcorrencias $ocorrencias) {}

    /**
     * Cria (instância nova) ou atualiza um ativo. A foto nova substitui a anterior;
     * $removerFoto apaga a atual sem enviar outra. Ao sair de "em operação", os planos
     * são inativados e as manutenções pendentes canceladas.
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
            DB::transaction(function () use ($ativo, $autor) {
                $saiuDeOperacao = $ativo->exists && $ativo->isDirty('status') && ! $ativo->emOperacao();
                $ativo->save();

                if ($saiuDeOperacao) {
                    $this->encerrarManutencoes($ativo, "Ativo com status alterado para {$ativo->status->rotulo()}.", $autor);
                }
            });
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
    public function excluir(Ativo $ativo, User $autor): void
    {
        DB::transaction(function () use ($ativo, $autor) {
            $this->encerrarManutencoes($ativo, 'Ativo excluído.', $autor);
            $ativo->delete();
        });
    }

    /** Quantas manutenções pendentes seriam canceladas se o ativo saísse de operação. */
    public function pendentes(Ativo $ativo): int
    {
        return $ativo->exists ? $ativo->manutencoes()->pendentes()->count() : 0;
    }

    private function encerrarManutencoes(Ativo $ativo, string $motivo, User $autor): void
    {
        $ativo->planos()->where('ativo', true)->get()->each->update(['ativo' => false]);
        $this->ocorrencias->cancelarPendentes($ativo->manutencoes()->getQuery(), $motivo, $autor, $ativo);
    }
}
