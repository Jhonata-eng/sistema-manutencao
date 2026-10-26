<?php

namespace App\Services;

use App\Models\Anexo;
use App\Models\Auditoria;
use App\Models\CorrecaoValor;
use App\Models\Manutencao;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Arquivos ficam no disco privado (storage/app/private/anexos/AAAA/MM), com nome aleatório e a
 * extensão deduzida do conteúdo; o download passa pela AnexoPolicy. Envio e exclusão são auditados
 * na manutenção.
 */
class AnexoService
{
    /**
     * Guarda o arquivo e cria o anexo. Chamar dentro de uma transação: se ela for desfeita,
     * o arquivo é apagado.
     *
     * @param  array{arquivo: UploadedFile, tipo: string, numero_nota: ?string, data_emissao: ?string, valor_nota: ?string}  $dados
     */
    public function armazenar(Manutencao $manutencao, array $dados, User $autor): Anexo
    {
        $arquivo = $dados['arquivo'];
        $caminho = $arquivo->store('anexos/'.now()->format('Y/m'), 'local');
        DB::afterRollBack(fn () => Storage::disk('local')->delete($caminho));

        $anexo = new Anexo([
            'tipo' => $dados['tipo'],
            'numero_nota' => $dados['numero_nota'] ?? null,
            'data_emissao' => $dados['data_emissao'] ?? null,
            'valor_nota' => $dados['valor_nota'] ?? null,
            'nome_original' => Str::limit(basename($arquivo->getClientOriginalName()), 250, ''),
            'caminho' => $caminho,
            'mime' => $arquivo->getMimeType(),
            'tamanho' => $arquivo->getSize(),
        ]);
        $anexo->manutencao_id = $manutencao->id;
        $anexo->enviado_por = $autor->id;
        $anexo->save();

        Auditoria::registrar('anexo_enviado', $manutencao, [], ['anexo' => $anexo->descricao()]);

        return $anexo;
    }

    /** Envio avulso, depois do registro (ex.: a nota chegou depois). */
    public function enviar(Manutencao $manutencao, array $dados, User $autor): Anexo
    {
        return DB::transaction(fn () => $this->armazenar($manutencao, $dados, $autor));
    }

    /** O comprovante de uma correção de valor não pode ser excluído (também garantido pela FK RESTRICT). */
    public function excluir(Anexo $anexo): void
    {
        if (CorrecaoValor::where('anexo_id', $anexo->id)->exists()) {
            throw ValidationException::withMessages([
                'protecao' => 'Este anexo é o comprovante de uma correção de valor e não pode ser excluído.',
            ]);
        }

        DB::transaction(function () use ($anexo) {
            $anexo->delete();
            Auditoria::registrar('anexo_excluido', $anexo->manutencao, ['anexo' => $anexo->descricao()]);
        });

        Storage::disk('local')->delete($anexo->caminho);
    }
}
