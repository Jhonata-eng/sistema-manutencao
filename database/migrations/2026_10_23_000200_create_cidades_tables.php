<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->char('uf', 2);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['nome', 'uf']);
        });

        // Cidades em que o usuário pode ver e operar ativos (ignorado com dados.ver_todas_cidades).
        Schema::create('cidade_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cidade_id')->constrained('cidades')->restrictOnDelete();
            $table->primary(['user_id', 'cidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cidade_user');
        Schema::dropIfExists('cidades');
    }
};
