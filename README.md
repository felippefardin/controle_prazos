# Controle de Prazos

Sistema simples em PHP + MySQL para controlar apenas prazos de processos.

## Requisitos

- XAMPP com Apache e MySQL
- PHP 8+
- MySQL/MariaDB

## Instalação

1. Copie a pasta `controle_prazos` para:

   `C:\xampp\htdocs\controle_prazos`

2. Abra o XAMPP e inicie:
   - Apache
   - MySQL

3. Acesse o phpMyAdmin:

   `http://localhost/phpmyadmin`

4. Clique em **Importar** e selecione o arquivo:

   `database.sql`

   Se o sistema já estava instalado, importe somente:

   `migracao_recuperacao_senha.sql`

5. Abra no navegador:

   `http://localhost/controle_prazos/`

## Primeiro acesso

- E-mail: `admin@local`
- Senha: `admin123`

## O que o sistema faz

- Cadastro de número do processo
- Assunto
- Responsável
- Data de entrada
- Data de vencimento
- Status
- Observações
- Contagem automática dos dias restantes
- Destaque de prazos vencidos
- Destaque de prazos que vencem hoje
- Destaque de prazos dos próximos 5 dias
- Aviso adicional para prazos entre 6 e 30 dias
- Filtros
- Pesquisa
- Marcar como concluído
- Editar e excluir
- Dashboard com resumo dos prazos
- Exportação da lista filtrada em PDF
- Exportação da lista filtrada em Excel (.xlsx)
- Exportação por situação e período de vencimento
- Cadastro de novos usuários pela tela de login
- Perfil com troca de senha e exclusão da própria conta
- Recuperação de senha por código de 6 dígitos enviado por e-mail
- Página exclusiva para processos concluídos
- Observações rápidas em todos os processos ainda abertos

## Envio de e-mail para recuperar senha

O sistema usa a função `mail()` do PHP. No XAMPP, configure o serviço de envio no `php.ini` e no `sendmail.ini`, depois reinicie o Apache.

No arquivo `config.php`, troque `MAIL_FROM` por um endereço válido do remetente:

```php
const MAIL_FROM = 'seu-email@dominio.com';
```

Cada código expira em 15 minutos, aceita no máximo 5 tentativas e perde a validade quando um novo código é solicitado ou a senha é alterada.

## Banco de dados

Configuração padrão do XAMPP em `config.php`:

```php
const DB_HOST = 'localhost';
const DB_NAME = 'controle_prazos';
const DB_USER = 'root';
const DB_PASS = '';
```

Se o seu MySQL tiver senha, altere `DB_PASS`.
