<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_equipamento_id')->constrained('tipos_equipamento')->restrictOnDelete();
            $table->string('marca', 60);
            $table->string('modelo', 100);
            $table->string('numero_serie', 100)->nullable();
            $table->unsignedSmallInteger('ano_fabricacao')->nullable();
            // Único inclusive entre ativos excluídos (soft delete): o número nunca é reaproveitado.
            $table->string('patrimonio', 50)->unique();
            $table->string('capacidade', 100)->nullable();
            $table->foreignId('cidade_id')->constrained('cidades')->restrictOnDelete();
            $table->string('localizacao', 150);
            $table->foreignId('responsavel_id')->nullable()->constrained('responsaveis')->restrictOnDelete();
            $table->date('data_aquisicao')->nullable();
            $table->string('status', 20)->default('em_operacao')->index();
            $table->text('observacoes')->nullable();
            $table->string('foto_path')->nullable();
            $table->foreignId('criado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ativos');
    }
};
