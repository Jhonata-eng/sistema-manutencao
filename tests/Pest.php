<?php

use App\Models\Cidade;
use App\Models\PlanoManutencao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/** Usuário com um perfil contendo exatamente estas permissões e vinculado a estas cidades. */
function usuarioCom(array $permissoes, Cidade ...$cidades): User
{
    $usuario = User::factory()->comPermissoes(...$permissoes)->create();
    $usuario->cidades()->attach(array_map(fn (Cidade $cidade) => $cidade->id, $cidades));

    return $usuario;
}

/** Imagem PNG válida de 1×1 px (UploadedFile::fake()->image() exigiria a extensão GD). */
function imagemFalsa(string $nome = 'foto.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nome, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
    ));
}

/** Dados válidos do formulário de plano (mensal, começando hoje, R$ 150,00). */
function dadosPlano(array $sobrescrever = []): array
{
    return [
        'descricao' => 'Limpeza de filtros',
        'periodicidade' => '1',
        'data_inicio' => today()->toDateString(),
        'valor_estimado' => '150,00',
        'ativo' => '1',
        ...$sobrescrever,
    ];
}

/** Datas previstas das ocorrências do plano (Y-m-d, em ordem), opcionalmente só de um status. */
function datasDoPlano(PlanoManutencao $plano, ?string $status = null): array
{
    return $plano->manutencoes()
        ->when($status, fn ($q) => $q->where('status', $status))
        ->orderBy('data_prevista')
        ->pluck('data_prevista')
        ->map(fn ($data) => $data->toDateString())
        ->all();
}
