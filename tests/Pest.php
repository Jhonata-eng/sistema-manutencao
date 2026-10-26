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
    return arquivoFalso('png', $nome);
}

/**
 * Arquivo com conteúdo real do tipo pedido: pdf, png, jpg, xml (NF-e) ou txt.
 * É um UploadedFile sobre um arquivo temporário de verdade, para getMimeType() usar o finfo
 * como em produção (o UploadedFile::fake() deduz o MIME pelo nome e não testaria o conteúdo).
 */
function arquivoFalso(string $tipo, ?string $nome = null, int $tamanhoExtra = 0): UploadedFile
{
    $conteudo = match ($tipo) {
        'pdf' => "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n",
        'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='),
        'jpg' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9",
        'xml' => '<?xml version="1.0" encoding="UTF-8"?><nfeProc versao="4.00"><NFe><infNFe Id="NFe123"/></NFe></nfeProc>',
        'txt' => 'apenas um texto qualquer',
    };

    $caminho = tempnam(sys_get_temp_dir(), 'anexo');
    file_put_contents($caminho, $conteudo.str_repeat(' ', $tamanhoExtra));

    return new UploadedFile($caminho, $nome ?? "arquivo.{$tipo}", null, null, true);
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
