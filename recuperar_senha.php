<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$erro = '';
$email = strtolower(trim($_POST['email'] ?? $_GET['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $codigo = preg_replace('/\D/', '', $_POST['codigo'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmacao_senha'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($codigo) !== 6) {
        $erro = 'E-mail ou código inválido.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmacao) {
        $erro = 'As senhas não coincidem.';
    } else {
        $stmt = db()->prepare(
            'SELECT r.id, r.usuario_id, r.codigo_hash, r.tentativas
             FROM recuperacoes_senha r
             INNER JOIN usuarios u ON u.id = r.usuario_id
             WHERE u.email = ? AND r.usado_em IS NULL AND r.expira_em >= NOW() AND r.tentativas < 5
             ORDER BY r.id DESC LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $recuperacao = $stmt->get_result()->fetch_assoc();

        if ($recuperacao) {
            $recuperacaoId = (int)$recuperacao['id'];
            $stmt = db()->prepare('UPDATE recuperacoes_senha SET tentativas = tentativas + 1 WHERE id = ?');
            $stmt->bind_param('i', $recuperacaoId);
            $stmt->execute();
        }

        if (!$recuperacao || !password_verify($codigo, $recuperacao['codigo_hash'])) {
            $erro = 'Código inválido ou expirado.';
        } else {
            $conn = db();
            $conn->begin_transaction();
            try {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $usuarioId = (int)$recuperacao['usuario_id'];
                $stmt = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
                $stmt->bind_param('si', $hash, $usuarioId);
                $stmt->execute();
                $stmt = $conn->prepare("UPDATE usuarios SET tentativas_login=0,bloqueado_em=NULL,situacao=IF(situacao='bloqueado','aprovado',situacao) WHERE id=?");
                $stmt->bind_param('i',$usuarioId);$stmt->execute();
                $stmt = $conn->prepare('UPDATE recuperacoes_senha SET usado_em = NOW() WHERE usuario_id = ? AND usado_em IS NULL');
                $stmt->bind_param('i', $usuarioId);
                $stmt->execute();
                $conn->commit();
                registrarAuditoria('senha_redefinida','usuario',$usuarioId);
                header('Location: index.php?senha_redefinida=1');
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                throw $e;
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redefinir senha - Controle de Prazos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-card">
    <div class="login-logo">CP</div>
    <h1>Redefinir senha</h1>
    <p>Use o código recebido por e-mail e escolha uma nova senha.</p>
    <?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>
    <form method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>E-mail
            <input type="email" name="email" required autocomplete="email" value="<?= e($email) ?>">
        </label>
        <label>Código de 6 dígitos
            <input type="text" name="codigo" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code">
        </label>
        <label>Nova senha
            <input type="password" name="senha" required minlength="8" autocomplete="new-password">
        </label>
        <label>Confirmar nova senha
            <input type="password" name="confirmacao_senha" required minlength="8" autocomplete="new-password">
        </label>
        <button class="btn btn-primary btn-block" type="submit">Alterar senha</button>
    </form>
    <div class="auth-links auth-links-single"><a href="index.php">Voltar ao login</a></div>
</div>
</body>
</html>
