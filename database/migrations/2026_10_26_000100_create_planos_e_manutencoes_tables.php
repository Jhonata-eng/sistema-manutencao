<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos_manutencao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ativo_id')->constrained('ativos')->restrictOnDelete();
            $table->string('descricao', 150);
            $table->unsignedSmallInteger('periodicidade_meses');
            $table->date('data_inicio');
            $table->date('data_termino')->nullable();
            $table->decimal('valor_estimado', 12, 2);
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->restrictOnDelete();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
        });

        // Ocorrências: programadas (geradas pelos planos) e não programadas, numa tabela só.
        Schema::create('manutencoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ativo_id')->constrained('ativos')->restrictOnDelete();
            $table->foreignId('plano_id')->nullable()->constrained('planos_manutencao')->restrictOnDelete();
            $table->string('tipo', 20);
            // Data de calendário que originou a ocorrência (data_inicio + k × periodicidade); não muda ao reagendar.
            $table->date('data_referencia')->nullable();
            $table->date('data_prevista')->nullable();
            $table->date('data_execucao')->nullable();
            $table->string('status', 20)->default('prevista');
            $table->decimal('valor_previsto', 12, 2)->nullable();
            $table->decimal('valor_real', 12, 2)->nullable();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->restrictOnDelete();
            $table->text('descricao')->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_cancelamento')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelado_em')->nullable();
            $table->timestamps();

            $table->unique(['plano_id', 'data_referencia']);
            $table->index(['status', 'data_prevista']);
            $table->index(['data_execucao', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manutencoes');
        Schema::dropIfExists('planos_manutencao');
    }
};
