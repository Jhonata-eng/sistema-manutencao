# Sistema de Controle de Manutenções Preventivas

> Especificação original fornecida pelo usuário em 23/10/2026. As interpretações e decisões aprovadas sobre pontos ambíguos estão em `docs/PROGRESSO.md`.

## Contexto
Você vai desenvolver um sistema web interno para um provedor de internet (ISP) que opera em várias cidades. O sistema controla equipamentos de infraestrutura (ar-condicionado, geradores, bebedouros industriais, nobreaks etc.) e as manutenções deles, preventivas e não programadas. O foco principal é o **controle de orçamento**: quanto está previsto gastar por mês, quanto foi gasto de fato e uma visão unificada de todas as manutenções.

## Como trabalhar
1. Antes de escrever código, leia toda esta especificação e me apresente:
   (a) a arquitetura proposta;
   (b) o modelo de dados (tabelas, campos, relacionamentos);
   (c) o plano de fases detalhado;
   (d) dúvidas ou ambiguidades.
   Aguarde minha aprovação antes de começar.
2. Crie um `CLAUDE.md` na raiz com a stack, as convenções, os comandos (rodar, testar, migrar) e as regras de negócio essenciais.
3. Mantenha um `docs/PROGRESSO.md` atualizado ao final de cada etapa, com o que foi feito, o que falta e as decisões tomadas. Ele deve permitir retomar o trabalho em uma nova sessão sem perda de contexto.
4. Desenvolva uma fase por vez. Ao final de cada fase, rode os testes, faça commit com mensagem descritiva e me mostre um resumo antes de seguir.
5. Se algo não estiver especificado, escolha a opção mais simples, registre a decisão no `PROGRESSO.md` e me avise. Não invente funcionalidades grandes por conta própria.

## Stack
- PHP 8.2+ com Laravel (versão estável atual)
- MySQL 8 (compatível com MariaDB 10.6+)
- Blade + Bootstrap 5 (renderização no servidor, sem SPA)
- Chart.js para gráficos; JavaScript puro ou Alpine.js para interações pontuais
- Interface em pt-BR, moeda R$ no formato 1.234,56, datas dd/mm/aaaa, timezone configurável (padrão America/Sao_Paulo)
- Valores monetários sempre em DECIMAL(12,2), nunca em float
- Deploy em servidor Linux (Nginx ou Apache + PHP-FPM). Entregue um README com instalação, `.env.example`, configuração do cron do scheduler e seeders.

## Módulos e requisitos

### 1. Autenticação e usuários
- Login com e-mail e senha (hash bcrypt/argon2), logout, "lembrar-me", limite de tentativas e expiração de sessão.
- Não há auto-cadastro público. Usuários são criados por quem tem permissão. Campos: nome, e-mail, perfil, cidades vinculadas, ativo/inativo.
- O usuário pode alterar a própria senha. O administrador pode redefinir a senha de outros. O sistema não deve depender de SMTP; envio de e-mail é opcional.
- O primeiro administrador é criado via seeder ou comando artisan.

### 2. Perfis e permissões (RBAC)
- Perfis configuráveis em tela, cada um com um conjunto de permissões granulares (checkboxes).
- Permissões mínimas:
  - dashboard.visualizar
  - ativos.visualizar, ativos.criar, ativos.editar, ativos.excluir
  - planos.visualizar, planos.criar, planos.editar, planos.excluir
  - manutencoes.visualizar, manutencoes.registrar_execucao, manutencoes.lancar_nao_programada, manutencoes.cancelar_reagendar, manutencoes.corrigir_valor
  - anexos.enviar, anexos.excluir
  - cadastros.gerenciar (cidades, responsáveis, tipos de equipamento, fornecedores)
  - usuarios.gerenciar, perfis.gerenciar
  - relatorios.exportar, auditoria.visualizar
  - dados.ver_todas_cidades
- Perfis padrão via seeder, todos editáveis: Administrador (todas as permissões), Gestor, Técnico e Visualizador.
- Escopo de dados: quem tem `dados.ver_todas_cidades` vê tudo. Os demais usuários veem e operam apenas ativos das cidades vinculadas a eles.
- Verifique permissões SEMPRE no backend (middleware/policies) e oculte na interface os menus e botões que o usuário não pode usar.

### 3. Cadastros auxiliares (tabelas configuráveis)
CRUD com ativar/inativar. Registros em uso não podem ser excluídos.
- Cidades: nome, UF.
- Responsáveis: nome, e-mail, telefone, setor, usuário do sistema vinculado (opcional).
- Tipos de equipamento: nome (ar-condicionado, gerador, bebedouro industrial, nobreak...).
- Fornecedores/prestadores: nome/razão social, CNPJ/CPF, telefone, e-mail.

### 4. Ativos (equipamentos)
- Campos: tipo, marca, modelo, número de série, ano de fabricação, nº de patrimônio (único), capacidade/especificação (ex.: 12.000 BTUs, 150 kVA), cidade, localização detalhada (ex.: POP Centro, sala técnica), responsável, data de aquisição, status (em operação, inativo, baixado), observações, foto (opcional), criado por.
- Listagem com busca, filtros (cidade, tipo, responsável, status) e paginação.
- Tela de detalhes com os dados do ativo, os planos de manutenção, o histórico completo de manutenções e o total gasto.
- Exclusão lógica (soft delete). Inativar ou dar baixa em um ativo cancela as ocorrências futuras pendentes, com confirmação.

### 5. Planos de manutenção recorrente
Um ativo pode ter vários planos (ex.: limpeza mensal + revisão anual).
- Campos: ativo, descrição do serviço, periodicidade (mensal, bimestral, trimestral, semestral, anual ou personalizada em N meses), data da primeira ocorrência, data de término (opcional), valor médio estimado, fornecedor padrão (opcional), observações, ativo/inativo.

Regras:
- Ao salvar o plano, gere as ocorrências com status "prevista" dentro de um horizonte configurável (padrão de 12 meses). Um comando agendado diário estende esse horizonte.
- A recorrência segue calendário fixo a partir da data inicial. Se o dia não existir no mês (ex.: 31), use o último dia do mês.
- Cada ocorrência guarda um `valor_previsto` copiado do plano no momento da geração.
- Ao editar o plano (periodicidade, datas, valor), recalcule apenas as ocorrências futuras pendentes. Ocorrências realizadas ou canceladas nunca são alteradas.
- Ao inativar o plano, cancele as ocorrências futuras pendentes.
- Exiba no plano a média real das execuções e ofereça a ação "atualizar valor médio com base no histórico".

### 6. Manutenções (ocorrências)
- Campos: ativo, plano (nulo quando não programada), tipo (programada | não programada/corretiva), data prevista, data de execução, status, valor previsto, valor real, fornecedor, descrição do serviço executado, observações, registrado por.
- Status: prevista, realizada ou cancelada. "Atrasada" é derivado: ocorrência prevista com data prevista anterior a hoje.
- Registrar execução de uma ocorrência prevista: data de execução, valor real (pré-preenchido com o previsto), fornecedor, descrição e anexos. Nesse momento a nota é opcional, porque pode chegar depois.
- Lançar manutenção não programada: selecionar o ativo (busca por patrimônio, modelo ou cidade) e informar data, valor, descrição, fornecedor e anexos.
- Cancelar ou reagendar uma ocorrência prevista exige justificativa obrigatória.
- Correção de valor após o registro:
  - exige a permissão `manutencoes.corrigir_valor`, um motivo e o anexo obrigatório da nota/comprovante;
  - grava um histórico com valor anterior, valor novo, motivo, anexo, usuário e data;
  - o histórico aparece na tela da manutenção.
- Manutenções realizadas sem nota anexada ficam sinalizadas como "sem comprovante" e podem ser filtradas.

### 7. Anexos
- Tipos aceitos: PDF, JPG, PNG e XML (NF-e). Tamanho máximo configurável (padrão de 10 MB). Valide o MIME real do arquivo.
- Metadados: tipo (nota fiscal, recibo, laudo, foto, outro), número da nota, data de emissão, valor da nota, enviado por.
- Armazene os arquivos fora da pasta pública. O download só acontece por rota autenticada que verifica permissão e escopo de cidade.

### 8. Dashboard (página inicial)
Todos os elementos respeitam o escopo de cidades do usuário e têm filtros por cidade, tipo de equipamento e responsável.
- Cards: previsto no mês atual, realizado no mês atual, manutenções atrasadas (quantidade e valor), próximas 30 dias, total de ativos.
- Gráfico de previsão: barras com o gasto previsto por mês nos próximos 12 meses. É a soma do valor previsto das ocorrências pendentes, agrupada pelo mês da data prevista.
- Gráfico de realizado: barras empilhadas dos últimos 12 meses, separando programadas e não programadas. Usa o valor real, agrupado pelo mês da data de execução, com uma linha do previsto para comparar previsto x realizado.
- Distribuição do gasto por tipo de equipamento e por cidade, com período selecionável.
- Listas de manutenções atrasadas e próximas, com atalho para registrar a execução.
- Clicar em uma barra do gráfico abre a visão unificada filtrada por aquele mês.

### 9. Visão unificada
- Listagem de todas as manutenções (programadas e não programadas) com filtros: período, status, cidade, tipo de equipamento, ativo, responsável, fornecedor, programada/não programada e "sem comprovante".
- Visão em calendário mensal.
- Totais do filtro no rodapé: previsto, realizado e diferença.
- Exportação da listagem filtrada em CSV/Excel.

### 10. Auditoria
- Registre criação, edição, exclusão e ações sensíveis (execução, cancelamento, correção de valor, alteração de permissões). Cada registro guarda usuário, data, IP e valores antes/depois.
- Tela de consulta para quem tem `auditoria.visualizar`.

## Requisitos não funcionais
- Segurança:
  - CSRF e validação server-side em todos os formulários;
  - escape de saída (XSS);
  - consultas apenas via ORM ou prepared statements;
  - bloqueio de acesso a registros de outras cidades por manipulação de ID na URL.
- Banco de dados:
  - migrations e seeders para todo o banco;
  - um seeder separado e opcional com dados de exemplo para testar o dashboard;
  - índices nas colunas de filtro e nas datas usadas pelos gráficos.
- Testes automatizados (feature) cobrindo no mínimo:
  - geração de ocorrências, incluindo o caso do dia 31 e a edição de plano;
  - permissões e escopo por cidade;
  - correção de valor com histórico;
  - cálculos do dashboard.
- Código organizado: controllers enxutos, regras de negócio em services e nenhuma lógica duplicada.

## Fora do escopo inicial
Não implementar agora, mas o modelo de dados não deve impedir:
- meta de orçamento anual/mensal por cidade ou tipo de equipamento;
- alertas por e-mail de manutenções próximas;
- importação de ativos via CSV;
- etiqueta com QR code do patrimônio.

## Fases
1. Setup do projeto, layout base, autenticação, usuários, perfis/permissões e auditoria base.
2. Cadastros auxiliares e ativos.
3. Planos recorrentes, geração de ocorrências e comando agendado.
4. Execução, manutenção não programada, anexos, cancelamento/reagendamento e correção de valor.
5. Dashboard e visão unificada (lista, calendário e exportação).
6. Revisão final de segurança e performance e README completo.
