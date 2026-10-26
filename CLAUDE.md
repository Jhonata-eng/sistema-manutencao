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
| Gerar previstas até o horizonte | `docker compose exec app php artisan manutencoes:estender-horizonte` (o serviço `scheduler` roda isso todo dia à 01:00) |
| Dados de demonstração (opcional, nunca em produção) | `docker compose exec app php artisan db:seed --class=DemonstracaoSeeder` |

Login de desenvolvimento (só no banco local): `admin@exemplo.com.br` / `admin12345`.

Rode os testes sempre **sem** cache de configuração (`php artisan config:clear` depois de `optimize`/`config:cache`): com cache, o `phpunit.xml` é ignorado e o `RefreshDatabase` recriaria o banco de desenvolvimento. O `tests/TestCase.php` aborta se o banco não terminar em `_testes`.

## Convenções

- Domínio em português: tabelas, models, rotas e variáveis (`planos_manutencao`, `usuarios/novo`). A tabela `users` mantém as colunas do Laravel (`name`, `email`, `password`).
- Controllers enxutos: validação em Form Requests (`app/Http/Requests`), regras de negócio em services (`app/Services`), filtros de listagem num `#[Scope] filtrar(array $filtros)` do model.
- Models com os atributos do Laravel 13 (`#[Table]`, `#[Fillable]`, `#[Hidden]`, `#[Scope]`). `Model::shouldBeStrict()` vale fora de produção: atributo fora do `#[Fillable]` lança exceção.
- Telas: `@extends('layouts.app')` + componentes de `resources/views/components` (`x-cabecalho`, `x-campo`, `x-selecao`, `x-interruptor-ativo`, `x-situacao`, `x-menu-item`). Sucesso em `session('sucesso')`; violação de regra de negócio como erro de validação na chave `protecao` (o `x-alertas` exibe).
- Campos de data sempre pelo componente `x-campo-data` (ponto único se o formato do campo mudar).
- Confirmação antes de enviar: `data-confirmar="mensagem"` no `<form>`.
- JavaScript só em arquivos de `public/js` ou `public/vendor`: a CSP (`CabecalhosSeguranca`) bloqueia `<script>` inline e `onclick=`. Dados para o JS vão num `<script type="application/json">`.
- Cadastros auxiliares usam o trait `CadastroAuxiliar`: filtro da listagem, `selecionaveis($atual)` nos selects e `regraSelecionavel($atual)` na validação, para que inativos não entrem em novos vínculos mas continuem nos registros que já os usam.
- Testes: helpers em `tests/Pest.php` (`usuarioCom([...permissões], ...$cidades)`, `arquivoFalso('pdf'|'jpg'|'png'|'xml'|'txt')`, `imagemFalsa()`, `dadosPlano()`). Para upload use `arquivoFalso()`: ele cria um arquivo real, então o MIME é detectado pelo conteúdo como em produção (o `UploadedFile::fake()` deduz pelo nome, e o container nem tem GD para `fake()->image()`).
- Listas cujas linhas usam `@can` sobre um registro precisam carregar antes (eager loading) as relações que a Policy consulta (ex.: `with('ativo')`): fora de produção, o modo estrito lança exceção ao carregar relação linha a linha.
- Somas de dinheiro: `selectRaw('COALESCE(SUM(col), 0) as total')->value('total')`. O `->sum()` do Laravel converte o resultado para float.
- Escopos de `Manutencao` qualificam as colunas (`manutencoes.status`), porque as consultas do painel fazem join com `ativos`, que também tem `status`.
- Gráficos: `public/js/graficos.js` (Chart.js estático) lê o JSON de `#dados-graficos`. Cores em ordem fixa azul `#2a78d6`, laranja `#eb6834`, verde-água `#1baf7a` (validadas para daltonismo); barras de até 24px com ponta de 4px; um só eixo de valores; cada gráfico tem uma tabela "Ver dados em tabela" logo abaixo.
- Rótulos de campos nas mensagens de validação: `lang/pt_BR/validation.php` → `attributes`.

## Regras essenciais

- **Permissões**: catálogo único em `Permissao::CATALOGO`, um Gate por chave. Rotas protegidas com `can:<chave>` ou Policy; menus e botões condicionados com `@can`. Permissão nova: adicionar ao catálogo e rodar `db:seed`.
- **Escopo por cidade**: sem `dados.ver_todas_cidades`, o usuário vê e opera apenas ativos (e tudo ligado a eles) das cidades vinculadas a ele. Listagens usam `visivelPara($usuario)` (`Ativo`, `Manutencao`, `Auditoria`); ações sobre um registro passam pela Policy (ex.: `AtivoPolicy`, via `#[Authorize]` no método do controller), que responde 403 para outra cidade. Selects de cidade usam `$usuario->cidadesAcessiveis()`.
- **Toda rota autenticada** é coberta por `tests/Feature/Seguranca/VarreduraRotasTest.php` (visitante → login; sem permissão → 403; outra cidade → 403). Rota nova liberada a qualquer usuário logado entra em `ROTAS_DE_QUALQUER_USUARIO`; parâmetro novo de registro com cidade entra em `PARAMETROS_COM_CIDADE`.
- **Gestão de usuários**: ninguém concede nem administra mais acesso do que tem (`User::podeConcederPerfil()`, `User::podeAdministrar()`, `UserPolicy`). Quem tem `perfis.gerenciar` pode se dar qualquer permissão: é nível de administrador.
- **Auditoria**: models de negócio usam o trait `Auditavel` (criação, edição, exclusão com antes/depois) e entram no morph map `Auditoria::TIPOS`. Ações sensíveis chamam `Auditoria::registrar($evento, $modelo, $antes, $depois, $motivo)` com um evento de `Auditoria::EVENTOS`. Relações N–N mudam via `sincronizarAuditando()`.
- **Proteção administrativa**: alterações de usuários e perfis passam por `ProtecaoAcessoAdministrativo::executar()`, que desfaz a operação se o sistema ficar sem usuário ativo com `usuarios.gerenciar` ou `perfis.gerenciar`.
- **Dinheiro**: `DECIMAL(12,2)` no banco, string decimal no PHP (cast `decimal:2`), somas feitas no SQL. Entrada do usuário por `Dinheiro::paraDecimal()`, exibição por `moeda()`.
- **Datas**: colunas `DATE`, "hoje" no fuso `APP_TIMEZONE`, exibição com `data_br()` / `data_hora_br()`, campos `type="date"`.
- **Cadastros em uso** são inativados, nunca excluídos: a exclusão passa por `ExclusaoProtegida`, que usa as FKs `RESTRICT` como definição de "em uso" (novas tabelas que referenciem um cadastro devem usar `restrictOnDelete()`).
- **Manutenções**: status gravados `prevista | realizada | cancelada`; "atrasada" e "sem comprovante" são calculados na consulta. Geração de ocorrências, edição de plano e cálculo de orçamento seguem `docs/PROGRESSO.md` → "Regras de negócio aprovadas".
- **Consultas de manutenções**: lista, calendário, exportação, totais e painel usam o `FiltroManutencoes` (escopo de cidades + filtros). O período usa por padrão a data efetiva: a coluna gerada e indexada `manutencoes.data_efetiva` = `COALESCE(data_execucao, data_prevista)`. Todo número do painel leva, ao ser clicado, a uma lista cujo rodapé soma o mesmo valor; um teste garante isso.
- **Desempenho** (medido com ~160 mil manutenções, ver `docs/PROGRESSO.md` → Fase 6): as somas do painel usam índices de cobertura (`manutencoes_realizado_index`, `_previsto_index`, `_pendentes_index`); as somas do realizado forçam o índice (`forceIndex`) porque o otimizador escolhe o de status. A exportação busca os IDs ordenados e carrega lotes pela chave primária (nunca `lazy()` com `OFFSET`).
- **Ações sobre manutenções** (execução, não programada, cancelar, reagendar, corrigir valor) passam pelo `ManutencaoService`, e anexos pelo `AnexoService`. A `ManutencaoPolicy` exige permissão + cidade + status (prevista para executar/cancelar/reagendar, realizada para anexar/corrigir). `Manutencao` não usa `Auditavel`: cada ação registra o próprio evento (`executada`, `reagendada`, `cancelada`, `valor_corrigido`, `anexo_enviado`...), mostrado na tela da manutenção.
- **Anexos**: tipo conferido pelo conteúdo (`mimetypes`) e pela extensão, tamanho de `ANEXO_TAMANHO_MAX_MB`, arquivo no disco privado com nome aleatório, download só pela rota `anexos.show` (AnexoPolicy); XML é sempre baixado, nunca aberto inline.
- **Ocorrências dos planos**: só o `GeradorOcorrencias` cria, recalcula ou cancela automaticamente as previstas (o `PlanoService` e o `AtivoService` o chamam). `(plano_id, data_referencia)` é único e impede duplicar; o cancelamento automático zera `data_referencia` para liberar a data se o plano voltar a ficar ativo, e o cancelamento manual a mantém.
- Em `PlanoManutencao`, a relação com o ativo é `equipamento()`: `$plano->ativo` é a coluna booleana de situação.

## Fluxo de trabalho

Uma fase por vez. Ao final de cada fase: testes verdes, `pint`, `docs/PROGRESSO.md` atualizado, commit descritivo e resumo ao usuário, aguardando aprovação antes da fase seguinte. O que não estiver especificado: escolha a opção mais simples, registre em `docs/PROGRESSO.md` → "Decisões" e avise o usuário.
