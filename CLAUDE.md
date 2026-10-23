# Controle de Manutenções Preventivas

Sistema web interno de um provedor de internet: ativos de infraestrutura (ar-condicionado, geradores, nobreaks…), planos de manutenção recorrente e controle de orçamento (previsto × realizado).

- `docs/ESPECIFICACAO.md`: requisitos completos, fonte da verdade do escopo.
- `docs/PROGRESSO.md`: fase atual, o que falta e as decisões já tomadas. Leia antes de qualquer tarefa; atualize ao final de cada etapa.

## Stack

Laravel 13 (PHP 8.3), MySQL 8.4 (compatível com MariaDB 10.6), Blade + Bootstrap 5.3, Chart.js e Alpine.js, testes com Pest 4.

Front sem etapa de build: bibliotecas estáticas versionadas em `public/vendor/<lib>/<versão>/`; código próprio em `public/css/app.css` e `public/js/app.js`, incluídos com `asset_versionado()`.

## Comandos

O PHP do host não tem as extensões do Laravel: rode PHP, Composer e Artisan dentro do container `app`.

| Ação | Comando |
|---|---|
| Subir o ambiente | `docker compose up -d` (app em http://localhost:8090, MySQL em localhost:3307) |
| Testes | `docker compose exec app php artisan test` (banco `manutencao_testes`) |
| Formatar | `docker compose exec app ./vendor/bin/pint` |
| Migrar | `docker compose exec app php artisan migrate` |
| Recriar o banco de dev | `docker compose exec app php artisan migrate:fresh --seed` e depois `app:criar-admin` |
| Criar administrador | `docker compose exec app php artisan app:criar-admin` |

Login de desenvolvimento (só no banco local): `admin@exemplo.com.br` / `admin12345`.

## Convenções

- Domínio em português: tabelas, models, rotas e variáveis (`planos_manutencao`, `usuarios/novo`). A tabela `users` mantém as colunas do Laravel (`name`, `email`, `password`).
- Controllers enxutos: validação em Form Requests (`app/Http/Requests`), regras de negócio em services (`app/Services`), filtros de listagem num `#[Scope] filtrar(array $filtros)` do model.
- Models com os atributos do Laravel 13 (`#[Table]`, `#[Fillable]`, `#[Hidden]`, `#[Scope]`). `Model::shouldBeStrict()` vale fora de produção: atributo fora do `#[Fillable]` lança exceção.
- Telas: `@extends('layouts.app')` + componentes de `resources/views/components` (`x-cabecalho`, `x-campo`, `x-selecao`, `x-interruptor-ativo`, `x-situacao`, `x-menu-item`). Sucesso em `session('sucesso')`; violação de regra de negócio como erro de validação na chave `protecao` (o `x-alertas` exibe).
- Campos de data sempre pelo componente `x-campo-data` (ponto único se o formato do campo mudar).
- Confirmação antes de enviar: `data-confirmar="mensagem"` no `<form>`.
- Cadastros auxiliares usam o trait `CadastroAuxiliar`: filtro da listagem, `selecionaveis($atual)` nos selects e `regraSelecionavel($atual)` na validação, para que inativos não entrem em novos vínculos mas continuem nos registros que já os usam.
- Testes: helpers em `tests/Pest.php` (`usuarioCom([...permissões], ...$cidades)`, `imagemFalsa()`; o container não tem GD, então `UploadedFile::fake()->image()` não funciona).
- Rótulos de campos nas mensagens de validação: `lang/pt_BR/validation.php` → `attributes`.

## Regras essenciais

- **Permissões**: catálogo único em `Permissao::CATALOGO`, um Gate por chave. Rotas protegidas com `can:<chave>` ou Policy; menus e botões condicionados com `@can`. Permissão nova: adicionar ao catálogo e rodar `db:seed`.
- **Escopo por cidade**: sem `dados.ver_todas_cidades`, o usuário vê e opera apenas ativos (e tudo ligado a eles) das cidades vinculadas a ele. Listagens usam `Ativo::visivelPara($usuario)`; ações sobre um registro passam pela Policy (ex.: `AtivoPolicy`, via `#[Authorize]` no método do controller), que responde 403 para outra cidade. Selects de cidade usam `$usuario->cidadesAcessiveis()`.
- **Auditoria**: models de negócio usam o trait `Auditavel` (criação, edição, exclusão com antes/depois) e entram no morph map `Auditoria::TIPOS`. Ações sensíveis chamam `Auditoria::registrar($evento, $modelo, $antes, $depois, $motivo)` com um evento de `Auditoria::EVENTOS`. Relações N–N mudam via `sincronizarAuditando()`.
- **Proteção administrativa**: alterações de usuários e perfis passam por `ProtecaoAcessoAdministrativo::executar()`, que desfaz a operação se o sistema ficar sem usuário ativo com `usuarios.gerenciar` ou `perfis.gerenciar`.
- **Dinheiro**: `DECIMAL(12,2)` no banco, string decimal no PHP (cast `decimal:2`), somas feitas no SQL. Entrada do usuário por `Dinheiro::paraDecimal()`, exibição por `moeda()`.
- **Datas**: colunas `DATE`, "hoje" no fuso `APP_TIMEZONE`, exibição com `data_br()` / `data_hora_br()`, campos `type="date"`.
- **Cadastros em uso** são inativados, nunca excluídos: a exclusão passa por `ExclusaoProtegida`, que usa as FKs `RESTRICT` como definição de "em uso" (novas tabelas que referenciem um cadastro devem usar `restrictOnDelete()`).
- **Manutenções**: status gravados `prevista | realizada | cancelada`; "atrasada" e "sem comprovante" são calculados na consulta. Geração de ocorrências, edição de plano e cálculo de orçamento seguem `docs/PROGRESSO.md` → "Regras de negócio aprovadas".

## Fluxo de trabalho

Uma fase por vez. Ao final de cada fase: testes verdes, `pint`, `docs/PROGRESSO.md` atualizado, commit descritivo e resumo ao usuário, aguardando aprovação antes da fase seguinte. O que não estiver especificado: escolha a opção mais simples, registre em `docs/PROGRESSO.md` → "Decisões" e avise o usuário.
