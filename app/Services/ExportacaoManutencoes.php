<?php

namespace App\Services;

use App\Enums\StatusManutencao;
use App\Models\Manutencao;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV da listagem filtrada no formato que o Excel em pt-BR abre direto: UTF-8 com BOM, separador ";",
 * vírgula decimal e datas dd/mm/aaaa. Gerado em fluxo, sem carregar tudo na memória.
 */
class ExportacaoManutencoes
{
    private const CABECALHO = [
        'ID', 'Data prevista', 'Data de execução', 'Status', 'Tipo', 'Patrimônio', 'Tipo de equipamento',
        'Marca/modelo', 'Cidade', 'Responsável', 'Serviço', 'Plano', 'Fornecedor', 'Valor previsto',
        'Valor real', 'Sem comprovante',
    ];

    /**
     * Primeiro busca só os IDs, já na ordem (consulta servida pelo índice de data_efetiva); depois
     * carrega lotes de 1.000 pela chave primária. Paginar com OFFSET reordenaria tudo a cada lote:
     * 12 meses com ~160 mil manutenções levavam ~22 s (docs/PROGRESSO.md, Fase 6).
     */
    public function csv(Builder $consulta): StreamedResponse
    {
        $ids = (clone $consulta)->reorder()
            ->orderByRaw(FiltroManutencoes::DATA_EFETIVA)
            ->orderBy('manutencoes.id')
            ->pluck('manutencoes.id');

        return response()->streamDownload(function () use ($ids) {
            // Exportações grandes podem passar do max_execution_time padrão; o arquivo já está sendo enviado.
            set_time_limit(0);
            $hoje = today()->toDateString();
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\u{FEFF}");
            fputcsv($saida, self::CABECALHO, ';', '"', '');

            foreach ($ids->chunk(1000) as $lote) {
                $porId = Manutencao::with(['ativo.cidade', 'ativo.tipo', 'ativo.responsavel', 'plano', 'fornecedor', 'anexos'])
                    ->whereKey($lote->all())
                    ->get()
                    ->keyBy('id');

                foreach ($lote as $id) {
                    fputcsv($saida, $this->linha($porId[$id], $hoje), ';', '"', '');
                }
            }

            fclose($saida);
        }, 'manutencoes-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function linha(Manutencao $manutencao, string $hoje): array
    {
        $ativo = $manutencao->ativo;
        $atrasada = $manutencao->status === StatusManutencao::Prevista && $manutencao->data_prevista?->toDateString() < $hoje;

        return [
            $manutencao->id,
            data_br($manutencao->data_prevista),
            data_br($manutencao->data_execucao),
            $atrasada ? 'Atrasada' : $manutencao->status->rotulo(),
            $manutencao->tipo->rotulo(),
            $this->texto($ativo->patrimonio),
            $this->texto($ativo->tipo->nome),
            $this->texto("{$ativo->marca} {$ativo->modelo}"),
            $this->texto($ativo->cidade->nome_uf),
            $this->texto($ativo->responsavel?->nome),
            $this->texto($manutencao->servico()),
            $this->texto($manutencao->plano?->descricao),
            $this->texto($manutencao->fornecedor?->nome),
            $this->valor($manutencao->valor_previsto),
            $this->valor($manutencao->valor_real),
            $manutencao->semComprovante() ? 'Sim' : 'Não',
        ];
    }

    /** "1234.56" → "1234,56" (sem separador de milhar, para qualquer planilha ler como número). */
    private function valor(?string $valor): string
    {
        return $valor === null ? '' : str_replace('.', ',', $valor);
    }

    /** Texto digitado por usuários não pode começar como fórmula de planilha (injeção de CSV). */
    private function texto(?string $texto): string
    {
        $texto = (string) $texto;

        return preg_match('/^[=+\-@\t\r]/', $texto) ? "'".$texto : $texto;
    }
}
