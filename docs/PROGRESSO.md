# Progresso

Última atualização: 26/10/2026, ao fim da Fase 6 (todas as fases da especificação concluídas).

## Situação

| Fase | Escopo | Situação |
|---|---|---|
| 1 | Setup, layout, autenticação, usuários, perfis/permissões, auditoria base | Concluída (commit `6a8e870`) |
| 2 | Cadastros auxiliares e ativos | Concluída (commit `2a688b5`) |
| 3 | Planos recorrentes, geração de ocorrências, comando agendado | Concluída (commit `5e28a76`) |
| 4 | Execução, não programada, anexos, cancelamento/reagendamento, correção de valor | Concluída (commit `dcb6fee`) |
| 5 | Dashboard e visão unificada (lista, calendário, exportação) | Concluída (commit `4336432`) |
| 6 | Revisão de segurança e performance, README completo | **Concluída**, aguardando aprovação |

## Fase 1: o que foi feito

- **Ambiente**: Docker Compose (`compose.yaml`, `docker/`) com PHP 8.3-FPM (UID 1000, sem arquivos root no volume), Nginx e MySQL 8.4. O init do MySQL cria o banco `manutencao_testes`.
- **Projeto**: Laravel 13.38, Pest 4, pt-BR (`lucascudo/laravel-pt-br-localization`), fuso `APP_TIMEZONE` (padrão America/Sao_Paulo), `config/manutencao.php` (horizonte, tamanho de anexo, tentativas de login).
- **Layout**: Bootstrap 5.3.8 + Bootstrap Icons 1.13.2 estáticos em `public/vendor`; menu lateral (offcanvas no celular) filtrado por `@can`; componentes Blade de formulário.
- **Autenticação**: login próprio (`LoginController`, `LoginRequest`) com "lembrar-me", 5 tentativas/minuto por e-mail+IP, sessão de 120 min; usuário inativo não entra e é desconectado no próximo acesso (`GarantirUsuarioAtivo`); troca da própria senha.
- **Usuários**: listagem com busca/filtros, criação, edição (perfil, cidades, ativo), redefinição de senha pelo administrador.
- **Perfis e permissões**: catálogo `Permissao::CATALOGO` (22 permissões da especificação), Gate por chave, tela de perfis com checkboxes por grupo, 4 perfis padrão (`PerfisSeeder`), `php artisan app:criar-admin`.
- **Proteção administrativa**: `ProtecaoAcessoAdministrativo` desfaz alterações que deixariam o sistema sem usuário ativo com `usuarios.gerenciar` ou `perfis.gerenciar`; ninguém inativa o próprio usuário.
- **Auditoria**: trait `Auditavel` (criado/editado/excluído com antes/depois, sem senha nem timestamps), `Auditoria::registrar()` para ações sensíveis, `sincronizarAuditando()` para N–N (permissões do perfil, cidades do usuário), tela com filtros e detalhes.
- **Utilitários**: `Dinheiro::formatar()` / `paraDecimal()` (sem float), helpers `moeda()`, `data_br()`, `data_hora_br()`, `asset_versionado()`.
- **Tabela `cidades`** já criada (necessária para vincular usuários); a tela de cadastro vem na Fase 2.
- **Testes**: 54 testes, 155 asserções (login, limite de tentativas, lembrar-me, inativo, senha, usuários, perfis, proteção, menu por permissão, auditoria, comando, dinheiro).

## Fase 2: o que foi feito

- **Cadastros auxiliares** (`cadastros.gerenciar`, rotas `cadastros.*`): cidades (nome + UF única, lista de UFs), tipos de equipamento (nome único; seeder com Ar-condicionado, Gerador, Bebedouro industrial e Nobreak), responsáveis (usuário do sistema opcional, no máximo um responsável por usuário) e fornecedores (CPF ou CNPJ opcional, validado e único, inclusive CNPJ alfanumérico). CRUD com ativar/inativar pela edição.
- **Exclusão protegida**: `ExclusaoProtegida` tenta excluir e, se uma FK `RESTRICT` impedir (erro 1451), devolve a mensagem "há registros vinculados, inative". Vale para ativos excluídos logicamente e para usuários vinculados a cidades.
- **Inativos**: trait `CadastroAuxiliar` (`selecionaveis()` / `regraSelecionavel()`) tira os inativos dos selects e da validação de novos vínculos, mas mantém o valor já gravado num registro em edição.
- **Ativos**: tabela com índices, soft delete, `criado_por`, status como enum `StatusAtivo`; listagem com busca (patrimônio, marca, modelo, série, local), filtros (cidade, tipo, responsável, status) e paginação; detalhes; foto opcional em disco privado servida por rota autenticada.
- **Escopo por cidade**: `Ativo::visivelPara($usuario)` nas listagens, `AtivoPolicy` (permissão + cidade) nas ações via `#[Authorize]`, cidade do formulário restrita às acessíveis e validada no backend.
- **Testes**: 115 testes, 310 asserções (Fase 1 + cadastros, CPF/CNPJ, ativos, foto, patrimônio único com excluídos, inativos, troca de ID na URL em 5 rotas, menu por permissão).

## Fase 3: o que foi feito

- **Tabelas** `planos_manutencao` e `manutencoes` (esta já com todos os campos de execução e cancelamento que a Fase 4 usa), chave única `(plano_id, data_referencia)`, índices `(status, data_prevista)` e `(data_execucao, tipo)`; enums `StatusManutencao` e `TipoManutencao`.
- **Recorrência** (`Recorrencia::datas`): k-ésima data = início + k × N meses sem overflow (dia 31 → último dia do mês; 29/02 → 28/02 nos anos não bissextos), janela com início inclusivo e fim exclusivo, término inclusivo.
- **`GeradorOcorrencias`**: `gerar` (janela de hoje até hoje + horizonte, `insertOrIgnore`), `recalcular` (mudança de calendário), `atualizarValorPrevisto`, `cancelarPendentes` (cancelamento automático com um evento de auditoria) e `estenderHorizonte` (comando diário).
- **`PlanoService`**: aplica as regras 1–4 e 11 ao salvar, inativar, reativar, excluir e "atualizar valor médio".
- **Comando** `manutencoes:estender-horizonte`, agendado todo dia à 01:00 (`routes/console.php`, `withoutOverlapping`); serviço `scheduler` (`schedule:work`) no `compose.yaml`.
- **Telas**: planos dentro do ativo (lista com próxima data na tela do ativo), formulário com periodicidade nomeada ou personalizada e valor em R$, detalhe do plano com média real e lista das manutenções; `PlanoManutencaoPolicy` (permissão + cidade).
- **Ativo fora de operação ou excluído**: inativa os planos e cancela todas as pendentes, com confirmação obrigatória (caixa validada no backend) na edição e contagem no aviso de exclusão.
- **Testes**: 152 testes, 437 asserções (recorrência pura, geração com dia 31, término, personalizada, validações, comando sem duplicar, reagendada/cancelada não recriadas, edição de valor e de periodicidade, reagendada de data passada, inativação/reativação, exclusão de plano, média real, acesso por cidade, ativo fora de operação).

## Fase 4: o que foi feito

- **Tabelas** `anexos` (índice `(manutencao_id, tipo)`) e `correcoes_valor` (FK `RESTRICT` para o comprovante); enum `TipoAnexo` (nota fiscal, recibo, laudo, foto, outro; comprovantes = nota fiscal e recibo).
- **`ManutencaoService`**: registrar execução, lançar não programada, cancelar, reagendar e corrigir valor, cada um com seu evento de auditoria. **`AnexoService`**: armazenar (apaga o arquivo se a transação for desfeita, via `DB::afterRollBack`), enviar avulso e excluir.
- **`ManutencaoPolicy`** (permissão + cidade + status) e **`AnexoPolicy`** (download para quem vê a manutenção; exclusão com `anexos.excluir`); `Manutencao::visivelPara()` inclui ativos excluídos (regra 13).
- **Telas**: página da manutenção (dados, reagendar/cancelar com justificativa, anexos com envio e exclusão, correções de valor, histórico de eventos), registro de execução (valor, fornecedor e serviço pré-preenchidos pelo plano), não programada (busca do ativo por patrimônio, marca/modelo ou cidade, depois o formulário), correção de valor (comprovante existente ou novo). Na tela do ativo: histórico (realizadas, canceladas e atrasadas), total gasto e atalho para não programada. Na tela do plano: atalhos para registrar e ver cada manutenção. Menu "Manutenções → Lançar não programada".
- **"Sem comprovante"**: `semComprovante()` no model e escopo `semComprovanteAnexado()` para os filtros da Fase 5; selo nas telas.
- **Correção de N+1** encontrada no teste no navegador: listas com `@can` por linha agora carregam `ativo` antes; `anexos()` usa `chaperone`. Teste de regressão com várias linhas e usuário com todas as permissões.
- **Testes**: 195 testes, 633 asserções (execução com anexo, sem comprovante, validações, permissões e status, não programada com busca e escopo, cancelamento que mantém a data ocupada, reagendamento com histórico, tipos de anexo pelo conteúdo, extensão, tamanho máximo, download protegido, XML como download, exclusão de anexo, correção de valor com histórico/comprovante existente ou novo/várias correções/validações, total gasto e histórico do ativo, escopo incluindo ativos excluídos).

## Fase 5: o que foi feito

- **`FiltroManutencoes`**: a consulta única (escopo + período + status, inclusive "atrasada" + cidade, tipo de equipamento, responsável, patrimônio, ativo, fornecedor, programada/não programada, sem comprovante) e os totais (previsto sem canceladas, realizado, diferença), somados no SQL. O período usa a data efetiva por padrão, com opção de data prevista ou de execução. `FiltrarManutencoesRequest` valida os filtros e converte `mes=AAAA-MM` no período do mês.
- **Visão unificada** (`/manutencoes`): lista paginada com filtros, totais do filtro no rodapé e atalhos para registrar e ver cada manutenção. **Calendário mensal** (`/manutencoes/calendario`), de domingo a sábado, com cor por status, legenda e totais. **Exportação CSV** (`/manutencoes/exportar`, exige `relatorios.exportar`): BOM, `;`, vírgula decimal, gerada em fluxo, com proteção contra fórmulas.
- **Painel** (`/`, exige `dashboard.visualizar`; sem a permissão, a pessoa vê uma página de boas-vindas): filtros de cidade, tipo de equipamento e responsável; 5 indicadores; previsão de 12 meses; realizado de 12 meses empilhado (programadas e não programadas) com a linha do previsto; distribuição do gasto por tipo e por cidade com período escolhido; listas de atrasadas e próximas com atalho "Registrar". Cada barra e cada indicador abrem a visão unificada com o mesmo recorte.
- **`DashboardService`**: todos os números vêm do `FiltroManutencoes`, agrupados por mês com `DATE_FORMAT` (MySQL/MariaDB), em string decimal.
- **Gráficos**: Chart.js 4.6.1 estático e `public/js/graficos.js`, com paleta validada pelo script da skill de visualização (azul, laranja e verde-água: daltonismo OK; o verde-água tem contraste < 3:1, compensado pela tabela de dados abaixo de cada gráfico).
- **`DemonstracaoSeeder`** (opcional): 20 ativos `DEMO-*`, cerca de 400 manutenções com histórico, notas, cancelamentos, atrasadas, não programadas e correções, além de dois usuários de exemplo.
- **Escopos de `Manutencao`** passaram a qualificar as colunas (necessário para os joins do painel).
- **Testes**: 223 testes, 743 asserções (cálculos do painel com cenário fixo, filtros do painel, número do card/barra = total da lista aberta, painel e boas-vindas, filtros e totais da visão unificada, data efetiva × prevista × execução, escopo, calendário e "+N mais", CSV com formato, filtro e proteção contra fórmulas, permissão de exportar, seeder de demonstração).

## Fase 6: o que foi feito

### Segurança

- **Varredura estática**: nenhum `{!! !!}`, `@csrf` em todos os formulários POST, nenhuma entrada sem validação, SQL bruto só com fragmentos internos e valores como parâmetros; `composer audit` sem vulnerabilidades.
- **Varredura de rotas** (`tests/Feature/Seguranca/VarreduraRotasTest.php`): percorre todas as rotas autenticadas registradas e exige login para visitante, 403 para usuário sem permissão e 403 para usuário de outra cidade (mesmo com todas as permissões). Validado removendo de propósito uma autorização: o teste falhou apontando a rota.
- **Corrigido: escalada de privilégio.** Quem tinha `usuarios.gerenciar` podia dar a qualquer um (até a si) o perfil Administrador ou redefinir a senha do administrador. Agora o perfil atribuído só pode ter permissões que o autor tem, as cidades vinculadas têm de ser acessíveis ao autor, e editar ou redefinir a senha de alguém exige ter todas as permissões e cidades dele (`UserPolicy`).
- **Corrigido: auditoria vazava dados de outras cidades.** O perfil Gestor (restrito às suas cidades) via, na auditoria, os valores de ativos, planos e manutenções de todas. `Auditoria::visivelPara()` aplica o escopo na lista e nos detalhes; eventos de cadastros, usuários e perfis continuam visíveis.
- **Corrigido: rota de upload direto no disco privado.** O esqueleto do Laravel 13 vem com `serve => true` no disco `local`, o que registra `GET/PUT storage/{path}` (com URL assinada). Desligado: fotos e anexos só saem pelos controllers.
- **Cabeçalhos** (`CabecalhosSeguranca`): CSP nas páginas HTML (scripts só do próprio servidor, `frame-ancestors 'none'`), `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`. Conferido no navegador: gráficos, menus, offcanvas e PDF inline funcionam, sem erros no console.
- **Páginas de erro** em português (403, 404, 419, 429, 500, 503), sem detalhes internos.
- **Trava nos testes**: o `TestCase` aborta se o banco não terminar em `_testes` (ver "Incidente" abaixo).

### Desempenho

Banco temporário com 1.000 ativos, 162.904 manutenções e 81.466 anexos (já removido). Tempos com cache aquecido, Docker no WSL2:

| Operação | Antes | Depois |
|---|---|---|
| Painel completo, administrador | ~2–3 s | ~0,25 s |
| Painel completo, usuário de 1 cidade | ~1–2,4 s | ~0,36 s |
| Lista do mês (página + totais) | ~0,55 s | 26–74 ms |
| Lista de 12 meses (página + totais) | ~0,65 s | ~0,12 s |
| Exportação CSV de 12 meses, todas as cidades (46 mil linhas): tempo de banco | 22,6 s | 2,2 s |
| Exportação CSV de 12 meses, total | ~23 s | ~15 s (o restante é montar e formatar os objetos no PHP) |

- Coluna gerada `manutencoes.data_efetiva` (armazenada e indexada) no lugar de `COALESCE(...)` nos filtros e ordenações.
- Índices de cobertura: `(data_execucao, status, tipo, ativo_id, valor_real)`, `(tipo, data_prevista, status, ativo_id, valor_previsto)` e `(status, data_prevista, ativo_id, valor_previsto)`; saíram `(data_execucao, tipo)` e `(status, data_prevista)`.
- `forceIndex` nas somas do realizado: o otimizador escolhia o índice de status (600 ms contra 60 ms com o de cobertura).
- Exportação: IDs ordenados numa consulta e lotes de 1.000 pela chave primária (o `OFFSET` refazia a ordenação a cada lote); `set_time_limit(0)` durante o envio.
- **MariaDB 10.6**: a suíte inteira (237 testes) passou num MariaDB 10.6.28, incluindo a coluna gerada e as dicas de índice.

### Documentação

- README completo: requisitos, instalação em Ubuntu com Nginx ou Apache + PHP-FPM, `.env` de produção, permissões, seeders, `optimize`, limites de upload, cron do agendador, atualização de versão, backup e tabela de segurança.
- `.env.example` com `SESSION_SECURE_COOKIE` e a nota de `APP_DEBUG=false` em produção.

### Testes

237 testes, 1.033 asserções, em MySQL 8.4 e MariaDB 10.6.

### Incidente durante a fase (ambiente de desenvolvimento)

Ao conferir o README, rodei `php artisan optimize` (cache de configuração) e depois a suíte de testes. Com o cache, o `phpunit.xml` é ignorado, e o `RefreshDatabase` recriou o banco **de desenvolvimento** (`manutencao`), apagando os dados de exemplo e o admin local. Nada de produção foi afetado. Providências:
- Trava no `tests/TestCase.php`: aborta antes de tocar no banco se ele não terminar em `_testes`. Testado com o cache ativo.
- Banco de desenvolvimento reconstruído com as mesmas credenciais, dados equivalentes aos feitos à mão nas fases 2 a 4 e o seeder de demonstração. Arquivos órfãos removidos.
- Aviso no `CLAUDE.md` e no README: nunca rodar testes com cache de configuração (em produção, isso apagaria o banco real se não fosse a trava).

## Possíveis evoluções (fora do escopo, não implementadas)

- Itens que a especificação lista como "fora do escopo inicial", que o modelo atual comporta sem mudanças:
  - **Metas de orçamento**: nova tabela `metas_orcamento` (cidade?, tipo de equipamento?, ano, mês?, valor) comparada com as somas do `DashboardService`.
  - **Alertas por e-mail**: usuários e responsáveis já têm e-mail; basta um comando agendado sobre `Manutencao::pendentes()`.
  - **Importação de ativos por CSV**: o patrimônio é único e serve como chave; cidades, tipos e responsáveis são buscados pelo nome.
  - **QR code do patrimônio**: a URL do ativo (`/ativos/{id}`) é estável e o patrimônio é único.
- Exportação anual muito grande mais rápida: gerar as linhas direto em SQL, sem montar os models (hoje ~15 s para 46 mil linhas).

## Modelo de dados

Todas as tabelas do modelo já existem: `users`, `perfis`, `permissoes`, `perfil_permissao`, `cidades`, `cidade_user`, `auditoria`, `tipos_equipamento`, `responsaveis`, `fornecedores`, `ativos`, `planos_manutencao`, `manutencoes`, `anexos`, `correcoes_valor` (+ `sessions`, `cache`, `jobs` do Laravel). Os campos estão nas migrations; abaixo, só as duas últimas:

```
anexos             id, manutencao_id, tipo(nota_fiscal|recibo|laudo|foto|outro), numero_nota?, data_emissao?,
                   valor_nota?, nome_original, caminho, mime, tamanho, enviado_por
correcoes_valor    id, manutencao_id, valor_anterior, valor_novo, motivo, anexo_id, user_id, created_at
```

Valores monetários em `DECIMAL(12,2)`. "Atrasada" e "sem comprovante" não são colunas: são calculados na consulta.

## Regras de negócio aprovadas

Respostas do usuário à proposta inicial (todas aprovadas em 23/10/2026):

1. **Recorrência**: a ocorrência k é sempre `data_inicio + k × N meses`; dia inexistente vai para o último dia do mês (31/01 → 28/02 → 31/03). `data_referencia` guarda essa data de calendário e, com a chave única (plano, data_referencia), a geração não duplica nem recria ocorrências reagendadas.
2. **Editar plano**: se mudar só o valor, atualiza o `valor_previsto` das pendentes futuras. Se mudar periodicidade ou datas, apaga as pendentes futuras e gera de novo a partir de hoje (reagendamento manual futuro se perde, fica na auditoria). Realizadas, canceladas e atrasadas não mudam.
3. **Inativar plano, inativar/dar baixa/excluir ativo**: cancela todas as pendentes, inclusive as atrasadas (com confirmação na tela).
4. **Excluir plano**: só se não tiver ocorrência realizada ou cancelada; senão, apenas inativar.
5. **Previsto do mês** (card e linha do previsto no gráfico de realizado): soma do `valor_previsto` das programadas com `data_prevista` no mês, status prevista ou realizada (exclui canceladas). O gráfico de previsão dos próximos 12 meses soma só as pendentes, como diz a especificação; por isso a barra do mês atual diminui conforme as execuções.
6. **Realizado do mês**: soma do `valor_real` pela `data_execucao`, programadas e não programadas.
7. **Não programada**: lançada já como realizada, data até hoje, sem `valor_previsto`; entra só no realizado.
8. **Sem comprovante**: realizada sem anexo do tipo nota fiscal ou recibo.
9. **Correção de valor**: o comprovante pode ser uma nota/recibo já anexado à manutenção ou um arquivo novo enviado no próprio formulário.
10. **Manutenção realizada**: não é editada nem cancelada; aceita só correção de valor (fluxo próprio) e novos anexos.
11. **"Atualizar valor médio"**: média do `valor_real` das execuções do plano; aplica a regra 2 (só valor) e exige `planos.editar`.
12. **Diferença no rodapé da visão unificada**: realizado − previsto (positivo = gasto acima do previsto).
13. **Ativo excluído (soft delete)**: o que já foi gasto continua nos totais e no dashboard, com o ativo marcado "(excluído)".
14. **Patrimônio**: único inclusive entre ativos excluídos.
15. **Histórico da manutenção**: execução, reagendamentos e cancelamento com justificativa (da auditoria) + tabela de correções de valor.
16. **Perfil único por usuário**; perfis padrão: Administrador (tudo), Gestor (tudo menos `usuarios.gerenciar`, `perfis.gerenciar`, `dados.ver_todas_cidades`), Técnico (visualizações + registrar execução + lançar não programada + enviar anexos), Visualizador (dashboard e visualizações).
17. **Exportação**: CSV com BOM, separador `;` e vírgula decimal (abre direto no Excel pt-BR); não gera .xlsx.
18. **Calendário**: montado no servidor em Blade.

## Decisões

### Ambiente e arquitetura (aprovadas)

- Projeto movido para `~/projetos/sistema-manutencao` (fora do OneDrive). A pasta original no OneDrive ficou vazia.
- Desenvolvimento com Docker Compose; produção em Linux com Nginx/Apache + PHP-FPM.
- Laravel 13 exige PHP 8.3: o servidor de produção precisa de PHP 8.3+.
- RBAC próprio (sem pacote), Gate por chave do catálogo.
- Front sem Vite/Node: removidos `package.json`, `vite.config.js` e Tailwind do esqueleto.
- Configuração no `.env`: `MANUTENCAO_HORIZONTE_MESES=12`, `ANEXO_TAMANHO_MAX_MB=10`, `LOGIN_MAX_TENTATIVAS=5`.

### Tomadas na Fase 1 (não especificadas; avisadas ao usuário)

- **Usuários não são excluídos**, só inativados (a especificação só prevê ativo/inativo, e eles são referenciados pela auditoria).
- **Redefinição de senha pelo administrador** encerra as sessões abertas e o "lembrar-me" do usuário; o administrador não redefine a própria senha por essa tela (é levado a "Alterar senha").
- **Login de usuário inativo** recebe a mesma mensagem de credenciais inválidas (não revela quais e-mails existem); quem é inativado com a sessão aberta é desconectado no próximo clique.
- **Login e logout não são auditados** (não pedido). Troca e redefinição de senha são auditadas, sem os valores.
- **Seeder idempotente**: rodar `db:seed` de novo não sobrescreve as permissões de perfis já existentes; `app:criar-admin` roda o seeder automaticamente.
- **Timestamps** (`created_at` etc.) gravados no fuso `APP_TIMEZONE`.
- **Campos de data** usam o seletor nativo (`type="date"`), que o navegador em pt-BR exibe como dd/mm/aaaa.
- O `CLAUDE.md`/`AGENTS.md` de exemplo que vem no esqueleto do Laravel 13 (instruções do Laravel Boost) foi substituído pelo `CLAUDE.md` do projeto.

### Tomadas na Fase 2 (não especificadas; avisadas ao usuário)

- **Cadastros são globais**: quem tem `cadastros.gerenciar` gerencia todas as cidades, responsáveis, tipos e fornecedores (não há escopo por cidade nos cadastros; o escopo vale para ativos e tudo ligado a eles).
- **Botão excluir sempre visível** nos cadastros; se o registro estiver em uso, a tela explica e sugere inativar (evita contar vínculos em cada linha).
- **Tipos de equipamento iniciais** no seeder: os quatro citados na especificação.
- **Responsável ↔ usuário**: um usuário do sistema pode estar vinculado a no máximo um responsável.
- **CPF/CNPJ** é opcional, gravado sem máscara e em maiúsculas; aceita o CNPJ alfanumérico (IN RFB 2.229/2024, emitido desde jul/2026). A busca aceita o número com ou sem máscara.
- **Foto do ativo**: JPG, PNG ou WebP até 5 MB, guardada em `storage/app/private/ativos/fotos`, entregue por `ativos/{ativo}/foto` com a mesma checagem de permissão e cidade dos detalhes.
- **Ativo**: data de aquisição não pode ser futura; ano de fabricação entre 1950 e o ano seguinte ao atual.
- **Usuário sem cidade vinculada** (e sem `dados.ver_todas_cidades`) não vê nenhum ativo.
- **Cancelamento de ocorrências ao inativar, dar baixa ou excluir ativo** fica para a Fase 3, quando as ocorrências existirem.
- **Campos de data**: seletor nativo (`type="date"`) centralizado em `x-campo-data`. Ele mostra dd/mm/aaaa quando o navegador está em português e mm/dd/aaaa quando está em inglês (verificado no Chromium). Pendente: o usuário decide se mantém ou troca por campo com máscara fixa dd/mm/aaaa.

### Tomadas na Fase 3 (não especificadas; avisadas ao usuário)

- **Janela de geração**: de hoje (ou da primeira ocorrência, se for depois) até hoje + horizonte, **fim exclusivo**. Um plano mensal tem 12 previstas à frente; um anual tem 1 (a seguinte entra quando faltar menos de 12 meses).
- **`gerado_ate` saiu do modelo**: a janela é recalculada a cada execução e a chave `(plano_id, data_referencia)` garante que nada se duplica.
- **Primeira ocorrência ≥ hoje no cadastro do plano**, para não criar atrasadas retroativas; na edição a data inicial pode ficar no passado. Manutenção já feita antes do plano entra como não programada.
- **Plano só fica ativo em ativo em operação** (e não excluído).
- **Ativo fora de operação ou excluído inativa os planos**, além de cancelar as pendentes; reativar o ativo não reativa os planos (o usuário reativa cada um, que volta a gerar a partir de hoje).
- **Confirmação** do cancelamento: caixa obrigatória, validada no backend, que aparece ao escolher Inativo/Baixado no ativo ou ao desligar o plano; na exclusão do ativo, o aviso do botão mostra quantas pendentes serão canceladas.
- **Cancelamento automático libera a data do calendário** (`data_referencia = NULL`), para o plano reativado gerar de novo; o cancelamento manual (Fase 4) mantém a data, para o comando diário não recriar o que o usuário cancelou.
- **Recálculo por mudança de calendário**: apaga as pendentes futuras cuja data de referência é de hoje em diante; uma pendente de data passada reagendada para o futuro é mantida (e recebe o novo valor, se o valor mudou).
- **Auditoria das ocorrências automáticas**: não há um registro por ocorrência gerada ou apagada; ficam os eventos `ocorrencias_recalculadas` e `ocorrencias_canceladas` (com quantidade e datas) no plano ou no ativo.
- **Periodicidade personalizada** de 1 a 120 meses. **Média real** arredondada a 2 casas; o botão "atualizar valor médio" só aparece quando a média difere do valor estimado.
- **Comando diário** à 01:00, sem sobreposição; em desenvolvimento roda no serviço `scheduler` do Compose.

### Tomadas na Fase 4 (não especificadas; avisadas ao usuário)

- **Anexos só em manutenção realizada** (são documentos da execução); uma prevista não recebe anexo.
- **Um anexo por vez no formulário** de execução e de não programada; os demais pelo botão "Enviar anexo" da tela da manutenção.
- **Descrição do serviço executado é obrigatória**, pré-preenchida com o serviço do plano.
- **Data de execução até hoje** (pode ser antes da data prevista). **Reagendamento**: nova data a partir de hoje e diferente da atual.
- **Não programada** pode ser lançada para qualquer ativo não excluído das cidades do usuário, inclusive inativo ou baixado.
- **Correção de valor**: quem tem `manutencoes.corrigir_valor` pode enviar o comprovante da correção mesmo sem `anexos.enviar`; o comprovante tem que ser nota fiscal ou recibo; usar um existente e enviar um novo ao mesmo tempo é recusado; o novo valor tem que ser diferente do atual.
- **Comprovante de correção não pode ser excluído.** Excluir um anexo apaga o arquivo do disco; a descrição dele fica na auditoria.
- **Download**: PDF e imagens abrem no navegador; XML é sempre baixado (um XML aberto no navegador poderia executar conteúdo ativo); `X-Content-Type-Options: nosniff`.
- **XML de NF-e** é reconhecido pelo conteúdo como `text/xml` quando começa com a declaração `<?xml ...?>` (padrão dos XMLs da SEFAZ); um XML sem a declaração pode ser identificado como texto e recusado.
- **Histórico na tela do ativo**: realizadas, canceladas e atrasadas; as pendentes futuras ficam na tela de cada plano (e na coluna "Próxima").
- **Arquivos** ficam com nome aleatório em `storage/app/private/anexos/AAAA/MM`; o nome original (até 250 caracteres) é usado só para exibição e no download.

### Tomadas na Fase 5 (não especificadas; avisadas ao usuário)

- **Visão unificada abre no mês atual** quando acessada sem filtros ("Limpar" volta ao mês atual); para ver tudo, apague as datas e filtre.
- **Período pela data efetiva** (execução se realizada, senão prevista), com a opção "Período pela" data prevista ou de execução. O indicador "Previsto no mês" e a linha do previsto abrem a lista por data prevista, para o total bater.
- **Status "Prevista (todas)"** inclui as atrasadas; "Atrasada" é um filtro à parte. O filtro de patrimônio aceita parte do número.
- **Rodapé**: previsto sem canceladas, realizado e diferença (realizado − previsto). Num período futuro a diferença aparece negativa, porque ainda não houve execução.
- **Calendário**: domingo a sábado, até 4 manutenções por dia e "+N mais" levando à lista do dia, posicionadas pela data efetiva.
- **CSV**: uma linha por manutenção, 16 colunas, valores sem separador de milhar; texto que começa com `= + - @` ganha um apóstrofo (para não virar fórmula). Sem filtros, exporta o mês atual, como a lista.
- **Painel**: "Próximos 30 dias" = de hoje até hoje + 30; o valor de "Atrasadas" é a soma do previsto; "Ativos" conta os não excluídos (com quantos estão em operação); a previsão começa no mês atual e inclui as atrasadas do próprio mês; o realizado vai até o mês atual; a distribuição mostra, por padrão, do dia 1º de 11 meses atrás até hoje, com todas as categorias (sem agrupar em "Outros").
- **Gráficos só no tema claro** (o sistema não tem tema escuro).
- **Seeder de demonstração** separado, que roda uma vez, exige um administrador e cria dois usuários de exemplo (`demo12345`). Não deve rodar em produção.

### Tomadas na Fase 6 (não especificadas; avisadas ao usuário)

- **Ninguém concede nem administra mais acesso do que tem** (perfil e cidades); o administrador não é afetado.
- **Auditoria com escopo por cidade** para quem não tem `dados.ver_todas_cidades` (eventos de ativos, planos e manutenções); cadastros, usuários e perfis continuam visíveis a quem tem `auditoria.visualizar`.
- **`perfis.gerenciar` é de nível administrador** (permite se dar qualquer permissão); documentado no README.
- **Rota `storage/{path}` do Laravel desligada** (`serve => false`).
- **CSP** sem scripts inline; estilos inline permitidos por causa dos atributos `style` das views. HSTS fica no servidor web.
- **Exportação sem limite de tempo de execução** durante o envio (o download começa logo; 12 meses de 1.000 ativos levam ~15 s).

## Observações para retomar

- Containers: `docker compose up -d` na raiz; o PHP do host não serve para rodar o projeto.
- Prints de verificação visual: Playwright via container `mcr.microsoft.com/playwright:v1.61.1-noble` com `--network host` (o Chromium do WSL não tem as bibliotecas do sistema). Para ver datas como um usuário brasileiro, lance o Chromium com `--lang=pt-BR`.
- O banco de desenvolvimento foi reconstruído na Fase 6: admin `admin@exemplo.com.br` / `admin12345`, AC-0001 (dois planos, execução com nota e correção, reagendamento), AC-0002, GER-0001 (não programada com XML), NB-0001 e o `DemonstracaoSeeder` (20 ativos `DEMO-*`, usuários `tecnico.demo` e `gestor.demo` com senha `demo12345`).
- Testes: sempre sem cache de configuração (`php artisan config:clear`).
- Em testes, use `$this->travelTo(...)` para fixar "hoje": as regras de pendente futura e atrasada dependem da data.
