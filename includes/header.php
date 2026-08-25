<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
$usuario = usuarioLogado();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Controle de Prazos') ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int)filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <div class="brand-mark">CP</div>
        <div>
            <strong>Controle de Prazos</strong>
            <small>Processos e vencimentos</small>
        </div>
    </div>

    <nav class="topnav">
        <a href="dashboard.php">Dashboard</a>
        <a href="prazos.php">Prazos</a>
        <a href="procuradores.php">Procuradores</a>
        <a href="concluidos.php">Concluídos</a>
        <a href="tutorial.php">Tutorial</a>
        <?php if (($usuario['perfil'] ?? '') === 'admin' && !adminEstaRepresentando()): ?><a href="admin_usuarios.php">Administração</a><a href="auditoria.php">Auditoria</a><?php endif; ?>
        <a class="btn btn-primary btn-sm" href="prazo_form.php">+ Novo processo</a>
    </nav>

    <div class="user-area">
        <?php if (adminEstaRepresentando()): ?><a class="btn btn-warning btn-sm" href="admin_voltar.php">Voltar ao ADMIN</a><?php endif; ?>
        <a class="profile-link" href="perfil.php"><?= e($usuario['nome']) ?></a>
        <a href="logout.php">Sair</a>
    </div>
</header>

<main class="container">
