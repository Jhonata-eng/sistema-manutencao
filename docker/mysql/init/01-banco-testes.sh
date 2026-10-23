#!/bin/bash
# Cria o banco usado pela suíte de testes (phpunit.xml) e dá acesso ao usuário da aplicação.
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS manutencao_testes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON manutencao_testes.* TO '$MYSQL_USER'@'%';
SQL
