<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
$nome = trim($_POST['nome'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$nomeUsuario = trim($_POST['nome_usuario'] ?? '');
$matricula = trim($_POST['matricula'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $senha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmacao_senha'] ?? '';

    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 120) {
        $erro = 'Informe um nome válido.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($cpf) !== 11) {
        $erro = 'Informe um CPF com 11 dígitos.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $nomeUsuario)) {
        $erro = 'O nome de usuário deve ter de 3 a 60 caracteres, sem espaços.';
    } elseif ($matricula === '' || mb_strlen($matricula) > 50) {
        $erro = 'Informe uma matrícula válida.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmacao) {
        $erro = 'As senhas não coincidem.';
    } else {
        $cpfHash = hashCpf($cpf);
        $stmt = db()->prepare('SELECT id FROM usuarios WHERE email = ? OR cpf_hash = ? OR nome_usuario = ? OR matricula = ? LIMIT 1');
        $stmt->bind_param('ssss', $email, $cpfHash, $nomeUsuario, $matricula);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            $erro = 'E-mail, CPF, nome de usuário ou matrícula já cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $cpfCriptografado = criptografarCpf($cpf);
            $stmt = db()->prepare("INSERT INTO usuarios (nome, email, cpf, cpf_hash, nome_usuario, matricula, senha, situacao) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')");
            $stmt->bind_param('sssssss', $nome, $email, $cpfCriptografado, $cpfHash, $nomeUsuario, $matricula, $hash);
            $stmt->execute();
            $usuarioId=(int)db()->insert_id; $codigo=(string)random_int(100000,999999); $codigoHash=password_hash($codigo,PASSWORD_DEFAULT);
            $stmt=db()->prepare("INSERT INTO verificacoes_email(usuario_id,tipo,codigo_hash,expira_em) VALUES (?,'cadastro',?,DATE_ADD(NOW(),INTERVAL 15 MINUTE))"); $stmt->bind_param('is',$usuarioId,$codigoHash); $stmt->execute();
            registrarAuditoria('usuario_cadastrado','usuario',$usuarioId,['nome'=>$nome,'nome_usuario'=>$nomeUsuario]);
            require_once __DIR__ . '/includes/email.php'; enviarCodigoVerificacao($email,$nome,$codigo,'Confirme seu cadastro');
            header('Location: confirmar_cadastro.php?email=' . urlencode($email));
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar usuário - Controle de Prazos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body cadastro-body">
<div class="login-card cadastro-card">
    <div class="login-logo">CP</div>
    <h1>Criar usuário</h1>
    <p>Preencha os dados para acessar o sistema.</p>

    <?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

    <form method="post" class="form cadastro-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label class="cadastro-wide">Nome completo
            <input type="text" name="nome" required maxlength="120" autocomplete="name" value="<?= e($nome) ?>">
        </label>
        <label>CPF<input type="text" name="cpf" required inputmode="numeric" maxlength="14" value="<?= e($_POST['cpf'] ?? '') ?>"></label>
        <label>Nome de usuário<input type="text" name="nome_usuario" required maxlength="60" autocomplete="username" value="<?= e($nomeUsuario) ?>"></label>
        <label>Matrícula<input type="text" name="matricula" required maxlength="50" value="<?= e($matricula) ?>"></label>
        <label>E-mail
            <input type="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($email) ?>">
        </label>
        <label>Senha
            <input type="password" name="senha" required minlength="8" autocomplete="new-password">
        </label>
        <label>Confirmar senha
            <input type="password" name="confirmacao_senha" required minlength="8" autocomplete="new-password">
        </label>
        <button class="btn btn-primary btn-block cadastro-wide" type="submit">Criar usuário</button>
    </form>
    <div class="auth-links auth-links-single cadastro-wide"><a href="index.php">Voltar para o login</a></div>
</div>
</body>
</html>
