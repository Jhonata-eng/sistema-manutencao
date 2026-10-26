<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Índices medidos com ~160 mil manutenções (docs/PROGRESSO.md, Fase 6):
 * - data_efetiva: a lista, o calendário e a exportação filtram e ordenam por COALESCE(execução, prevista),
 *   que não usa índice; a coluna gerada armazenada permite índice (MySQL 8 e MariaDB 10.6).
 * - realizado/previsto/pendentes: índices de cobertura para as somas do painel (o valor sai do próprio
 *   índice, sem ler a linha). O de pendentes substitui o (status, data_prevista) original.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manutencoes', function (Blueprint $table) {
            $table->date('data_efetiva')->nullable()->storedAs('COALESCE(data_execucao, data_prevista)')->after('data_execucao');
            $table->index('data_efetiva');
            $table->dropIndex(['data_execucao', 'tipo']);
            $table->index(['data_execucao', 'status', 'tipo', 'ativo_id', 'valor_real'], 'manutencoes_realizado_index');
            $table->index(['tipo', 'data_prevista', 'status', 'ativo_id', 'valor_previsto'], 'manutencoes_previsto_index');
            $table->dropIndex(['status', 'data_prevista']);
            $table->index(['status', 'data_prevista', 'ativo_id', 'valor_previsto'], 'manutencoes_pendentes_index');
        });
    }

    public function down(): void
    {
        Schema::table('manutencoes', function (Blueprint $table) {
            $table->dropIndex('manutencoes_pendentes_index');
            $table->index(['status', 'data_prevista']);
            $table->dropIndex('manutencoes_previsto_index');
            $table->dropIndex('manutencoes_realizado_index');
            $table->index(['data_execucao', 'tipo']);
            $table->dropIndex(['data_efetiva']);
            $table->dropColumn('data_efetiva');
        });
    }
};
