<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();

$erro = '';
$sucesso = '';
$usuarioId = (int)$_SESSION['usuario_id'];

$stmt = db()->prepare('SELECT id, nome, email, senha FROM usuarios WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$usuarioBanco = $stmt->get_result()->fetch_assoc();

if (!$usuarioBanco) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $acao = $_POST['acao'] ?? '';
    $senhaAtual = $_POST['senha_atual'] ?? '';

    if (!password_verify($senhaAtual, $usuarioBanco['senha'])) {
        $erro = 'A senha atual está incorreta.';
    } elseif ($acao === 'alterar_senha') {
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmacao = $_POST['confirmacao_senha'] ?? '';

        if (strlen($novaSenha) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($novaSenha !== $confirmacao) {
            $erro = 'As novas senhas não coincidem.';
        } elseif (password_verify($novaSenha, $usuarioBanco['senha'])) {
            $erro = 'A nova senha deve ser diferente da senha atual.';
        } else {
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = db()->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $usuarioId);
            $stmt->execute();
            $stmt = db()->prepare('UPDATE recuperacoes_senha SET usado_em = NOW() WHERE usuario_id = ? AND usado_em IS NULL');
            $stmt->bind_param('i', $usuarioId);
            $stmt->execute();
            $usuarioBanco['senha'] = $hash;
            $sucesso = 'Senha alterada com sucesso.';
            registrarAuditoria('senha_alterada','usuario',$usuarioId);
        }
    } elseif ($acao === 'excluir_perfil') {
        if(($_SESSION['usuario_perfil']??'')==='admin'){$erro='A conta administradora principal não pode ser excluída por esta tela.';}
        else {
        $confirmacao = trim($_POST['confirmacao_exclusao'] ?? '');
        if ($confirmacao !== 'EXCLUIR') {
            $erro = 'Digite EXCLUIR para confirmar a remoção do perfil.';
        } else {
            $stmt = db()->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->bind_param('i', $usuarioId);
            $stmt->execute();

            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            header('Location: index.php?perfil_excluido=1');
            exit;
        }
        }
    } else {
        $erro = 'Ação inválida.';
    }
}

$titulo = 'Meu perfil';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <h1>Meu perfil</h1>
        <p>Gerencie sua senha e sua conta.</p>
    </div>
</section>

<?php if ($erro): ?><div class="alert alert-danger alert-page"><?= e($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success alert-page"><?= e($sucesso) ?></div><?php endif; ?>

<div class="profile-grid">
    <section class="panel profile-panel">
        <h2>Dados da conta</h2>
        <dl class="profile-data">
            <div><dt>Nome</dt><dd><?= e($usuarioBanco['nome']) ?></dd></div>
            <div><dt>E-mail</dt><dd><?= e($usuarioBanco['email']) ?></dd></div>
        </dl>
    </section>

    <section class="panel profile-panel">
        <h2>Alterar senha</h2>
        <form method="post" class="form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="acao" value="alterar_senha">
            <label>Senha atual<input type="password" name="senha_atual" required autocomplete="current-password"></label>
            <label>Nova senha<input type="password" name="nova_senha" required minlength="8" autocomplete="new-password"></label>
            <label>Confirmar nova senha<input type="password" name="confirmacao_senha" required minlength="8" autocomplete="new-password"></label>
            <button class="btn btn-primary" type="submit">Salvar nova senha</button>
        </form>
    </section>

    <section class="panel profile-panel danger-zone">
        <h2>Excluir perfil</h2>
        <p>Seu usuário será removido permanentemente. Os prazos cadastrados serão preservados sem vínculo com o perfil.</p>
        <form method="post" class="form" onsubmit="return confirm('Tem certeza de que deseja excluir seu perfil permanentemente?');">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="acao" value="excluir_perfil">
            <label>Senha atual<input type="password" name="senha_atual" required autocomplete="current-password"></label>
            <label>Digite EXCLUIR para confirmar<input type="text" name="confirmacao_exclusao" required autocomplete="off"></label>
            <button class="btn btn-danger" type="submit">Excluir meu perfil</button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
