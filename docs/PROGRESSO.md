# Progresso

Última atualização: 26/10/2026, ao fim da Fase 3.

## Situação

| Fase | Escopo | Situação |
|---|---|---|
| 1 | Setup, layout, autenticação, usuários, perfis/permissões, auditoria base | Concluída (commit `6a8e870`) |
| 2 | Cadastros auxiliares e ativos | Concluída (commit `2a688b5`) |
| 3 | Planos recorrentes, geração de ocorrências, comando agendado | **Concluída**, aguardando aprovação |
| 4 | Execução, não programada, anexos, cancelamento/reagendamento, correção de valor | Próxima |
| 5 | Dashboard e visão unificada (lista, calendário, exportação) | — |
| 6 | Revisão de segurança e performance, README completo | — |

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

## Próximos passos (Fase 4)

1. Registrar execução de uma prevista (data, valor real pré-preenchido com o previsto, fornecedor com padrão do plano, descrição, anexos opcionais) com `registrado_por` e auditoria `executada`.
2. Lançar não programada: busca de ativo por patrimônio, modelo ou cidade (só ativos acessíveis); já nasce realizada (regra 7).
3. Cancelar e reagendar previstas com justificativa obrigatória (cancelamento manual mantém `data_referencia`; reagendar muda só `data_prevista`), auditados com motivo.
4. Anexos: tabela `anexos` (FK `restrictOnDelete`), tipos PDF/JPG/PNG/XML validados pelo MIME real, tamanho de `ANEXO_TAMANHO_MAX_MB`, metadados, armazenamento privado, download protegido por permissão e cidade, exclusão com `anexos.excluir`.
5. Correção de valor (regra 9): tabela `correcoes_valor`, motivo e comprovante obrigatórios, histórico na tela da manutenção.
6. Tela da manutenção (dados, anexos, histórico); "sem comprovante" calculado (regra 8); histórico completo e total gasto na tela do ativo.
7. `Manutencao::visivelPara($usuario)` incluindo ativos excluídos (regra 13) e `ManutencaoPolicy`.

## Modelo de dados planejado

Tabelas já criadas: `users`, `perfis`, `permissoes`, `perfil_permissao`, `cidades`, `cidade_user`, `auditoria`, `tipos_equipamento`, `responsaveis`, `fornecedores`, `ativos`, `planos_manutencao`, `manutencoes` (+ `sessions`, `cache`, `jobs` do Laravel). As demais (Fase 4):

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

## Observações para retomar

- Containers: `docker compose up -d` na raiz; o PHP do host não serve para rodar o projeto.
- Prints de verificação visual: Playwright via container `mcr.microsoft.com/playwright:v1.61.1-noble` com `--network host` (o Chromium do WSL não tem as bibliotecas do sistema). Para ver datas como um usuário brasileiro, lance o Chromium com `--lang=pt-BR`.
- O banco de desenvolvimento tem dados de exemplo criados à mão (Campinas, Sorocaba, Itu inativa, 4 ativos, 2 fornecedores, 1 responsável, 2 planos no AC-0001). O seeder de demonstração oficial vem na Fase 5.
- Em testes, use `$this->travelTo(...)` para fixar "hoje": as regras de pendente futura e atrasada dependem da data.
