<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'controle_prazos';
const DB_USER = 'root';
const DB_PASS = '';

const MAIL_FROM = 'contatotech.tecnologia@gmail.com';
const APP_NAME = 'Controle de Prazos';

$arquivoSecreto = 'C:/xampp/controle_prazos_email_secret.php';
if (is_file($arquivoSecreto)) require_once $arquivoSecreto;
if (!defined('SMTP_USER')) define('SMTP_USER', MAIL_FROM);
if (!defined('SMTP_PASS')) define('SMTP_PASS', '');

date_default_timezone_set('America/Sao_Paulo');
