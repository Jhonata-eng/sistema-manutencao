# Controle de Manutenções Preventivas

Sistema web interno para controlar equipamentos de infraestrutura (ar-condicionado, geradores, nobreaks, bebedouros industriais…), seus planos de manutenção e o orçamento previsto × realizado, por cidade.

Stack: Laravel 13 (PHP 8.3), MySQL 8 / MariaDB 10.6+, Blade + Bootstrap 5.

> O guia completo de instalação em produção (Nginx/Apache + PHP-FPM, cron do agendador, permissões de pastas) será concluído na Fase 6. Esta versão cobre o ambiente de desenvolvimento.

## Desenvolvimento (Docker Compose)

Pré-requisitos: Docker com o plugin Compose.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan app:criar-admin
```

Acesse http://localhost:8090 e entre com o administrador criado. O serviço `scheduler` do Compose roda o agendador do Laravel (equivalente ao cron de produção), que gera todo dia as manutenções previstas dos planos.

| Ação | Comando |
|---|---|
| Testes | `docker compose exec app php artisan test` |
| Formatar código | `docker compose exec app ./vendor/bin/pint` |
| Recriar o banco | `docker compose exec app php artisan migrate:fresh --seed` |
| Gerar previstas agora | `docker compose exec app php artisan manutencoes:estender-horizonte` |

As portas podem ser trocadas no `.env` (`APP_PORT`, `FORWARD_DB_PORT`).

## Seeders

- `DatabaseSeeder` (padrão): permissões e os perfis Administrador, Gestor, Técnico e Visualizador. Pode ser rodado de novo sem duplicar nem sobrescrever perfis editados.
- Primeiro administrador: `php artisan app:criar-admin` (interativo, ou com `--nome`, `--email` e `--senha`).
- `DemonstracaoSeeder` (opcional, **só para testes**): `php artisan db:seed --class=DemonstracaoSeeder`. Cria 20 ativos `DEMO-*` em quatro cidades com 12 meses de histórico e 12 de previsão, para ver o painel com dados. Exige um administrador, roda uma única vez e cria dois usuários de exemplo com a senha `demo12345`: `tecnico.demo@exemplo.com.br` (Técnico, Campinas) e `gestor.demo@exemplo.com.br` (Gestor, Sorocaba e Jundiaí). Não rode em produção.

## Configuração específica (`.env`)

| Variável | Padrão | Uso |
|---|---|---|
| `APP_TIMEZONE` | `America/Sao_Paulo` | Fuso usado para datas e para o "hoje" das regras |
| `SESSION_LIFETIME` | `120` | Minutos de inatividade até a sessão expirar |
| `LOGIN_MAX_TENTATIVAS` | `5` | Tentativas por e-mail + IP antes de bloquear por 1 minuto |
| `MANUTENCAO_HORIZONTE_MESES` | `12` | Meses à frente em que as ocorrências dos planos são geradas |
| `ANEXO_TAMANHO_MAX_MB` | `10` | Tamanho máximo de cada anexo |
