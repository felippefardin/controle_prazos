<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_set_cookie_params(['lifetime'=>0,'path'=>'/controle_prazos/','domain'=>'','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'),'httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function exigirAdmin(): void { exigirLogin(); if(($_SESSION['usuario_perfil']??'')!=='admin' || !empty($_SESSION['admin_original_id'])){http_response_code(403);exit('Acesso exclusivo do administrador.');} }
function adminEstaRepresentando(): bool { return !empty($_SESSION['admin_original_id']); }

function exigirLogin(): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: index.php');
        exit;
    }
}

function usuarioLogado(): array
{
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nome' => $_SESSION['usuario_nome'] ?? '',
        'email' => $_SESSION['usuario_email'] ?? '',
        'perfil' => $_SESSION['usuario_perfil'] ?? 'usuario',
    ];
}
