# Controle de Manutenções Preventivas

Sistema web interno para controlar equipamentos de infraestrutura (ar-condicionado, geradores, nobreaks, bebedouros industriais…), seus planos de manutenção e o orçamento previsto × realizado, por cidade.

- **Ativos** com foto, responsável, localização e status; escopo de dados por cidade.
- **Planos recorrentes** que geram as manutenções previstas (calendário fixo, horizonte de 12 meses estendido todo dia).
- **Execução**, manutenção **não programada**, **cancelamento e reagendamento** com justificativa, **correção de valor** com comprovante e histórico.
- **Anexos** (PDF, JPG, PNG, XML da NF-e) guardados fora da pasta pública.
- **Painel** de orçamento, **visão unificada**, **calendário** e **exportação CSV**.
- **Perfis e permissões** configuráveis e **auditoria** de todas as alterações.

Stack: Laravel 13 (PHP 8.3), MySQL 8 ou MariaDB 10.6+, Blade + Bootstrap 5, Chart.js. Não há etapa de build de front-end: as bibliotecas estão em `public/vendor`.

---

## Instalação em produção (Linux)

O exemplo usa Ubuntu 24.04, Nginx e PHP-FPM; a seção [Apache](#apache) mostra a alternativa.

### 1. Requisitos

| Componente | Versão / observação |
|---|---|
| PHP | **8.3 ou superior** (o Laravel 13 exige 8.3), com PHP-FPM |
| Extensões PHP | `mbstring`, `xml`, `curl`, `zip`, `mysql` (pdo_mysql), `intl`, `bcmath`, `fileinfo` (confere o tipo real dos anexos), `opcache` |
| Banco | MySQL 8.0+ ou MariaDB 10.6+ (`utf8mb4`) |
| Servidor web | Nginx ou Apache 2.4 |
| Outros | Composer 2, `git`, `unzip`, `cron` |

```bash
sudo apt update
sudo apt install nginx mysql-server git unzip \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath php8.3-opcache
# Composer: https://getcomposer.org/download/
```

### 2. Banco de dados

```sql
CREATE DATABASE manutencao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'manutencao'@'localhost' IDENTIFIED BY 'troque-esta-senha';
GRANT ALL PRIVILEGES ON manutencao.* TO 'manutencao'@'localhost';
```

### 3. Código e dependências

```bash
sudo mkdir -p /var/www/manutencao && sudo chown $USER: /var/www/manutencao
git clone <url-do-repositorio> /var/www/manutencao
cd /var/www/manutencao
composer install --no-dev --optimize-autoloader
```

### 4. Configuração (`.env`)

```bash
cp .env.example .env
php artisan key:generate
```

Ajuste no `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://manutencao.suaempresa.com.br
APP_TIMEZONE=America/Sao_Paulo

DB_HOST=127.0.0.1
DB_DATABASE=manutencao
DB_USERNAME=manutencao
DB_PASSWORD=troque-esta-senha

SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

Apague as linhas da seção "Somente desenvolvimento com Docker Compose" (`APP_PORT`, `FORWARD_DB_PORT`, `DB_ROOT_PASSWORD`). As variáveis próprias do sistema estão em [Configuração](#configuração-env).

> Guarde uma cópia do `.env` (principalmente a `APP_KEY`) junto do backup: sem ela, sessões e cookies "lembrar-me" deixam de valer.

### 5. Permissões de pastas

O PHP-FPM (usuário `www-data`) precisa escrever em `storage/` (logs, views compiladas, **anexos e fotos**) e em `bootstrap/cache/`:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

### 6. Banco, perfis e primeiro administrador

```bash
php artisan migrate --force
php artisan db:seed --force          # permissões, perfis padrão e tipos de equipamento
php artisan app:criar-admin          # pede nome, e-mail e senha
```

### 7. Otimização (repetir a cada atualização)

```bash
php artisan optimize                  # cache de configuração, rotas, views e eventos
```

Deixe o OPcache ligado no PHP-FPM (padrão nos pacotes do Ubuntu).

### 8. Limites de upload do PHP

Os anexos têm até `ANEXO_TAMANHO_MAX_MB` (10 MB por padrão) e as fotos de ativo até 5 MB. Em `/etc/php/8.3/fpm/conf.d/99-manutencao.ini`:

```ini
upload_max_filesize = 12M
post_max_size = 25M
memory_limit = 256M
expose_php = Off
```

`upload_max_filesize` precisa ser maior que `ANEXO_TAMANHO_MAX_MB`. Depois: `sudo systemctl reload php8.3-fpm`.

### 9. Nginx

`/etc/nginx/sites-available/manutencao`:

```nginx
server {
    listen 80;
    server_name manutencao.suaempresa.com.br;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name manutencao.suaempresa.com.br;
    root /var/www/manutencao/public;          # só a pasta public fica exposta
    index index.php;
    charset utf-8;

    ssl_certificate     /etc/ssl/certs/manutencao.crt;
    ssl_certificate_key /etc/ssl/private/manutencao.key;
    add_header Strict-Transport-Security "max-age=31536000" always;

    client_max_body_size 25M;                 # igual ou maior que post_max_size

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/manutencao /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Os demais cabeçalhos de segurança (CSP, `X-Frame-Options`, `nosniff`, `Referrer-Policy`) são enviados pela própria aplicação.

### Apache

Habilite `mod_rewrite`, `mod_ssl`, `mod_headers` e o PHP-FPM (`a2enconf php8.3-fpm`, `a2enmod proxy_fcgi setenvif`). O `public/.htaccess` já faz o roteamento:

```apache
<VirtualHost *:443>
    ServerName manutencao.suaempresa.com.br
    DocumentRoot /var/www/manutencao/public

    <Directory /var/www/manutencao/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/manutencao.crt
    SSLCertificateKeyFile /etc/ssl/private/manutencao.key
    Header always set Strict-Transport-Security "max-age=31536000"
    LimitRequestBody 26214400
</VirtualHost>
```

### 10. Agendador (cron)

O sistema gera todo dia, à 01:00 (no fuso `APP_TIMEZONE`), as manutenções previstas que entram no horizonte de 12 meses. O Laravel decide o horário; o cron só precisa chamá-lo a cada minuto, **como `www-data`**:

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/manutencao && php artisan schedule:run >> /dev/null 2>&1
```

Para conferir: `php artisan schedule:list`. Para rodar na hora: `php artisan manutencoes:estender-horizonte`.

### 11. Conferência final

- Abra o endereço, entre com o administrador criado e cadastre uma cidade e um ativo.
- `php artisan about` mostra ambiente, debug (deve estar **OFF**) e caches.
- Uma página inexistente deve mostrar "Página não encontrada", sem detalhes técnicos.

---

## Atualização de versão

```bash
cd /var/www/manutencao
php artisan down                       # página "Sistema em manutenção"
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
sudo systemctl reload php8.3-fpm
php artisan up
```

## Backup

Três coisas, juntas:

1. **Banco**:
   ```bash
   mysqldump --single-transaction --routines -u manutencao -p manutencao | gzip > manutencao-$(date +%F).sql.gz
   ```
2. **Arquivos enviados** (anexos e fotos): `storage/app/private/`.
3. **`.env`** (contém a `APP_KEY` e as senhas).

Para restaurar: importe o dump, devolva `storage/app/private/` com dono `www-data` e o `.env`, e rode `php artisan optimize`.

---

## Segurança: o que já vem configurado e o que depende do servidor

| Item | Onde |
|---|---|
| Senhas com bcrypt, limite de 5 tentativas de login por minuto (e-mail + IP), sessão de 120 min | aplicação |
| Permissão e escopo por cidade verificados no backend em todas as rotas (um teste percorre todas) | aplicação |
| Ninguém concede nem administra mais acesso do que tem; o sistema nunca fica sem administrador | aplicação |
| CSRF, validação no servidor, saída escapada, consultas parametrizadas | aplicação |
| Anexos fora da pasta pública, tipo conferido pelo conteúdo, download só com permissão | aplicação |
| CSP, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, páginas de erro sem detalhes | aplicação |
| HTTPS, HSTS e `SESSION_SECURE_COOKIE=true` | servidor / `.env` |
| `APP_DEBUG=false` | `.env` |

O perfil com `perfis.gerenciar` pode dar a si qualquer permissão: trate-o como administrador. Quem não tem `dados.ver_todas_cidades` vê, na auditoria, só os eventos de ativos, planos e manutenções das suas cidades.

---

## Desenvolvimento (Docker Compose)

Pré-requisito: Docker com o plugin Compose.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan app:criar-admin
```

Acesse http://localhost:8090. O serviço `scheduler` do Compose faz o papel do cron.

| Ação | Comando |
|---|---|
| Testes | `docker compose exec app php artisan test` |
| Formatar código | `docker compose exec app ./vendor/bin/pint` |
| Recriar o banco | `docker compose exec app php artisan migrate:fresh --seed` |
| Gerar previstas agora | `docker compose exec app php artisan manutencoes:estender-horizonte` |

As portas podem ser trocadas no `.env` (`APP_PORT`, `FORWARD_DB_PORT`). Os testes usam o banco `manutencao_testes`, criado pelo próprio container do MySQL.

## Seeders

- `DatabaseSeeder` (padrão): permissões, perfis Administrador, Gestor, Técnico e Visualizador e os tipos de equipamento iniciais. Pode ser rodado de novo sem duplicar nem sobrescrever perfis editados.
- Primeiro administrador: `php artisan app:criar-admin` (interativo, ou com `--nome`, `--email` e `--senha`).
- `DemonstracaoSeeder` (opcional, **só para testes**): `php artisan db:seed --class=DemonstracaoSeeder`. Cria 20 ativos `DEMO-*` em quatro cidades com 12 meses de histórico e 12 de previsão, para ver o painel com dados. Exige um administrador, roda uma única vez e cria dois usuários de exemplo com a senha `demo12345`: `tecnico.demo@exemplo.com.br` (Técnico, Campinas) e `gestor.demo@exemplo.com.br` (Gestor, Sorocaba e Jundiaí). **Não rode em produção.**

## Configuração (`.env`)

| Variável | Padrão | Uso |
|---|---|---|
| `APP_TIMEZONE` | `America/Sao_Paulo` | Fuso das datas, do "hoje" das regras e do horário do agendador |
| `SESSION_LIFETIME` | `120` | Minutos de inatividade até a sessão expirar |
| `SESSION_SECURE_COOKIE` | `false` | `true` em produção (HTTPS) |
| `LOGIN_MAX_TENTATIVAS` | `5` | Tentativas por e-mail + IP antes de bloquear por 1 minuto |
| `MANUTENCAO_HORIZONTE_MESES` | `12` | Meses à frente em que as manutenções dos planos são geradas |
| `ANEXO_TAMANHO_MAX_MB` | `10` | Tamanho máximo de cada anexo (ajuste também o `upload_max_filesize` do PHP) |

## Documentação do projeto

- `docs/ESPECIFICACAO.md`: requisitos originais.
- `docs/PROGRESSO.md`: o que foi feito em cada fase, regras de negócio aprovadas, decisões e medições de desempenho.
- `CLAUDE.md`: convenções e regras para quem for manter o código.
