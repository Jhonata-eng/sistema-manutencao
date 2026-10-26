<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manutencao_id')->constrained('manutencoes')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->string('numero_nota', 50)->nullable();
            $table->date('data_emissao')->nullable();
            $table->decimal('valor_nota', 12, 2)->nullable();
            $table->string('nome_original');
            // Caminho no disco privado (storage/app/private); nunca exposto em URL pública.
            $table->string('caminho');
            $table->string('mime', 100);
            $table->unsignedInteger('tamanho');
            $table->foreignId('enviado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['manutencao_id', 'tipo']);
        });

        Schema::create('correcoes_valor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manutencao_id')->constrained('manutencoes')->restrictOnDelete();
            $table->decimal('valor_anterior', 12, 2);
            $table->decimal('valor_novo', 12, 2);
            $table->text('motivo');
            // RESTRICT: o comprovante de uma correção não pode ser excluído.
            $table->foreignId('anexo_id')->constrained('anexos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correcoes_valor');
        Schema::dropIfExists('anexos');
    }
};
