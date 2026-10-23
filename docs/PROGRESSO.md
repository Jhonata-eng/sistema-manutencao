# Progresso

Última atualização: 23/10/2026, ao fim da Fase 2.

## Situação

| Fase | Escopo | Situação |
|---|---|---|
| 1 | Setup, layout, autenticação, usuários, perfis/permissões, auditoria base | Concluída (commit `6a8e870`) |
| 2 | Cadastros auxiliares e ativos | **Concluída**, aguardando aprovação |
| 3 | Planos recorrentes, geração de ocorrências, comando agendado | Próxima |
| 4 | Execução, não programada, anexos, cancelamento/reagendamento, correção de valor | — |
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

## Próximos passos (Fase 3)

1. Migrations `planos_manutencao` e `manutencoes` (com `data_referencia`, chave única plano + data_referencia, índices de status/datas), FKs `restrictOnDelete()` para fornecedor.
2. `GeradorOcorrencias`: cálculo de datas (dia 31, 29/02, data de término), geração no horizonte, recálculo ao editar plano (regras 1–4 abaixo), cancelamento ao inativar plano.
3. Comando diário `manutencoes:estender-horizonte` no agendador (`routes/console.php`) e serviço `scheduler` no `compose.yaml`.
4. Telas de planos (dentro do ativo), com média real e "atualizar valor médio" (regra 11).
5. Ativo: ao mudar para inativo/baixado ou excluir, pedir confirmação e cancelar as pendentes (regra 3); seção de planos na tela de detalhes.
6. Testes pesados de datas e de edição de plano.

## Modelo de dados planejado

Tabelas já criadas: `users`, `perfis`, `permissoes`, `perfil_permissao`, `cidades`, `cidade_user`, `auditoria`, `tipos_equipamento`, `responsaveis`, `fornecedores`, `ativos` (+ `sessions`, `cache`, `jobs` do Laravel). As demais, por fase:

```
planos_manutencao  id, ativo_id, descricao, periodicidade_meses, data_inicio, data_termino?, valor_estimado,
                   fornecedor_id?, observacoes?, ativo, gerado_ate
manutencoes        id, ativo_id, plano_id?, tipo(programada|nao_programada), data_referencia?, data_prevista?,
                   data_execucao?, status(prevista|realizada|cancelada), valor_previsto?, valor_real?,
                   fornecedor_id?, descricao?, observacoes?, registrado_por?, motivo_cancelamento?,
                   cancelado_por?, cancelado_em?     único(plano_id, data_referencia)
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

## Observações para retomar

- Containers: `docker compose up -d` na raiz; o PHP do host não serve para rodar o projeto.
- Prints de verificação visual: Playwright via container `mcr.microsoft.com/playwright:v1.61.1-noble` com `--network host` (o Chromium do WSL não tem as bibliotecas do sistema). Para ver datas como um usuário brasileiro, lance o Chromium com `--lang=pt-BR`.
- O banco de desenvolvimento tem dados de exemplo criados à mão (Campinas, Sorocaba, Itu inativa, 4 ativos, 2 fornecedores, 1 responsável). O seeder de demonstração oficial vem na Fase 5.
