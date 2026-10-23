<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_equipamento', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60)->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('responsaveis', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->index();
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('setor', 60)->nullable();
            // Usuário do sistema que é este responsável (no máximo um responsável por usuário).
            $table->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150)->index();
            // CPF (11 dígitos) ou CNPJ (14 caracteres, numérico ou alfanumérico), sem máscara.
            $table->string('documento', 14)->nullable()->unique();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedores');
        Schema::dropIfExists('responsaveis');
        Schema::dropIfExists('tipos_equipamento');
    }
};
