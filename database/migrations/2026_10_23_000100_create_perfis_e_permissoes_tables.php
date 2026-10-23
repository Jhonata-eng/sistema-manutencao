<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60)->unique();
            $table->string('descricao')->nullable();
            $table->timestamps();
        });

        // Catálogo fixo, sincronizado pelo PermissoesSeeder a partir de Permissao::CATALOGO.
        Schema::create('permissoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 60)->unique();
            $table->string('descricao');
            $table->string('grupo', 40);
            $table->timestamps();
        });

        Schema::create('perfil_permissao', function (Blueprint $table) {
            $table->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $table->foreignId('permissao_id')->constrained('permissoes')->cascadeOnDelete();
            $table->primary(['perfil_id', 'permissao_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('perfil_id')->after('password')->constrained('perfis')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('perfil_id');
        });
        Schema::dropIfExists('perfil_permissao');
        Schema::dropIfExists('permissoes');
        Schema::dropIfExists('perfis');
    }
};
