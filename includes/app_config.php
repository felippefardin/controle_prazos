<?php
declare(strict_types=1);

$appEnv = is_file(__DIR__ . '/../.env')
    ? (parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW) ?: [])
    : [];

$appEnvValue = static function (string $key, string $default = '') use ($appEnv): string {
    $systemValue = getenv($key);
    return (string) ($systemValue !== false ? $systemValue : ($appEnv[$key] ?? $default));
};

defined('APP_NAME') || define('APP_NAME', $appEnvValue('APP_NAME', 'Controle de Prazos'));
defined('DB_HOST') || define('DB_HOST', $appEnvValue('DB_HOST', 'localhost'));
defined('DB_USER') || define('DB_USER', $appEnvValue('DB_USERNAME', 'root'));
defined('DB_PASS') || define('DB_PASS', $appEnvValue('DB_PASSWORD'));
defined('DB_NAME') || define('DB_NAME', $appEnvValue('DB_DATABASE', 'controle_prazos'));
defined('SMTP_USER') || define('SMTP_USER', $appEnvValue('SMTP_USER'));
defined('SMTP_PASS') || define('SMTP_PASS', $appEnvValue('SMTP_PASSWORD'));
defined('EMAIL_ENCRYPTION_KEY') || define('EMAIL_ENCRYPTION_KEY', $appEnvValue('APP_KEY'));

date_default_timezone_set('America/Sao_Paulo');
