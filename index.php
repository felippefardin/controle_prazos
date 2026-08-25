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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $nomeUsuario = trim($_POST['nome_usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nomeUsuario === '' || $senha === '') {
        $erro = 'Informe nome de usuário e senha.';
    } else {
        $stmt = db()->prepare('SELECT id, nome, email, nome_usuario, senha, email_verificado_em, perfil, situacao, tentativas_login FROM usuarios WHERE nome_usuario = ? LIMIT 1');
        $stmt->bind_param('s', $nomeUsuario);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if ($usuario && $usuario['situacao']==='bloqueado') {
            registrarAuditoria('login_bloqueado','usuario',(int)$usuario['id']);
            $erro='Usuário bloqueado. Use “Esqueci minha senha” ou procure o administrador.';
        } elseif ($usuario && password_verify($senha, $usuario['senha']) && $usuario['email_verificado_em'] && $usuario['situacao']==='aprovado') {
            $uid=(int)$usuario['id'];$stmt=db()->prepare('UPDATE usuarios SET tentativas_login=0,bloqueado_em=NULL,ultimo_login_em=NOW() WHERE id=?');$stmt->bind_param('i',$uid);$stmt->execute();
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $uid;
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];
            registrarAuditoria('login_sucesso','usuario',$uid);

            header('Location: dashboard.php');
            exit;
        } elseif ($usuario && password_verify($senha,$usuario['senha']) && !$usuario['email_verificado_em']) {
            $erro='Confirme o código enviado ao seu e-mail antes de entrar.';
        } elseif ($usuario && password_verify($senha,$usuario['senha']) && $usuario['situacao']==='pendente') {
            $erro='Cadastro confirmado e aguardando aprovação do administrador.';
        } else {
            if($usuario){$uid=(int)$usuario['id'];$tentativas=(int)$usuario['tentativas_login']+1;$bloquear=$tentativas>=5;$stmt=db()->prepare("UPDATE usuarios SET tentativas_login=?,situacao=IF(?=1,'bloqueado',situacao),bloqueado_em=IF(?=1,NOW(),bloqueado_em) WHERE id=?");$b=$bloquear?1:0;$stmt->bind_param('iiii',$tentativas,$b,$b,$uid);$stmt->execute();registrarAuditoria($bloquear?'usuario_bloqueado_5_tentativas':'login_falhou','usuario',$uid,['tentativa'=>$tentativas]);}
            $erro='Nome de usuário ou senha inválidos.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar - Controle de Prazos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-card">
    <div class="login-logo">CP</div>
    <h1>Controle de Prazos</h1>
    <p>Entre para acompanhar os vencimentos dos processos.</p>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= e($erro) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['senha_redefinida'])): ?>
        <div class="alert alert-success">Senha alterada. Entre com a nova senha.</div>
    <?php endif; ?>
    <?php if (isset($_GET['cadastro_confirmado'])): ?><div class="alert alert-success">E-mail confirmado. Seu cadastro aguarda aprovação do administrador.</div><?php endif; ?>
    <?php if (isset($_GET['perfil_excluido'])): ?>
        <div class="alert alert-success">Perfil excluído com sucesso.</div>
    <?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>
            Nome de usuário
            <input type="text" name="nome_usuario" required autocomplete="username" placeholder="Seu nome de usuário">
        </label>

        <label>
            Senha
            <input type="password" name="senha" required autocomplete="current-password" placeholder="••••••••">
        </label>

        <button class="btn btn-primary btn-block" type="submit">Entrar</button>
    </form>

    <div class="auth-links">
        <a href="esqueci_senha.php">Esqueci minha senha</a>
        <a href="cadastro.php">Criar novo usuário</a>
    </div>

</div>
</body>
</html>
