<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/email.php';

$mensagem = '';
$erro = '';
$email = strtolower(trim($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (!empty($_SESSION['ultima_recuperacao']) && time() - (int)$_SESSION['ultima_recuperacao'] < 60) {
        $erro = 'Aguarde um minuto antes de solicitar outro código.';
    } else {
        $_SESSION['ultima_recuperacao'] = time();
        $stmt = db()->prepare('SELECT id, nome, email FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if ($usuario) {
            $codigo = (string)random_int(100000, 999999);
            $hash = password_hash($codigo, PASSWORD_DEFAULT);
            $usuarioId = (int)$usuario['id'];

            $stmt = db()->prepare('UPDATE recuperacoes_senha SET usado_em = NOW() WHERE usuario_id = ? AND usado_em IS NULL');
            $stmt->bind_param('i', $usuarioId);
            $stmt->execute();

            $stmt = db()->prepare('INSERT INTO recuperacoes_senha (usuario_id, codigo_hash, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))');
            $stmt->bind_param('is', $usuarioId, $hash);
            $stmt->execute();

            if (!enviarCodigoRecuperacao($usuario['email'], $usuario['nome'], $codigo)) {
                error_log('Não foi possível enviar o código de recuperação para o usuário ' . $usuarioId);
            }
        }

        $mensagem = 'Se o e-mail estiver cadastrado e o envio estiver configurado, o código chegará em alguns instantes.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Esqueci a senha - Controle de Prazos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-card">
    <!-- <div class="login-logo">CP</div> -->
    <h1>Esqueci a senha</h1>
    <p>Informe seu e-mail para receber um código válido por 15 minutos.</p>

    <?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>E-mail
            <input type="email" name="email" required autocomplete="email" value="<?= e($email) ?>">
        </label>
        <button class="btn btn-primary btn-block" type="submit">Enviar código</button>
    </form>
    <div class="auth-links">
        <a href="index.php">Voltar ao login</a>
        <a href="recuperar_senha.php<?= $email ? '?email=' . urlencode($email) : '' ?>">Já tenho o código</a>
    </div>
</div>
</body>
</html>
