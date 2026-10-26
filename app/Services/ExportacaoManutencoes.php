<?php

namespace App\Services;

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

    public function csv(Builder $consulta): StreamedResponse
    {
        $consulta->with(['ativo.cidade', 'ativo.tipo', 'ativo.responsavel', 'plano', 'fornecedor', 'anexos'])
            ->orderByRaw(FiltroManutencoes::DATA_EFETIVA)
            ->orderBy('manutencoes.id');

        return response()->streamDownload(function () use ($consulta) {
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\u{FEFF}");
            fputcsv($saida, self::CABECALHO, ';', '"', '');

            foreach ($consulta->lazy(500) as $manutencao) {
                fputcsv($saida, $this->linha($manutencao), ';', '"', '');
            }

            fclose($saida);
        }, 'manutencoes-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function linha(Manutencao $manutencao): array
    {
        $ativo = $manutencao->ativo;

        return [
            $manutencao->id,
            data_br($manutencao->data_prevista),
            data_br($manutencao->data_execucao),
            $manutencao->estaAtrasada() ? 'Atrasada' : $manutencao->status->rotulo(),
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
