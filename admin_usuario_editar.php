<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirAdmin();

$conn = db();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(422);
    exit('Usuário inválido.');
}

$stmt = $conn->prepare(
    'SELECT id, nome, email, cpf, cpf_hash, nome_usuario, matricula, perfil, situacao,
            tentativas_login, email_verificado_em, criado_em
     FROM usuarios
     WHERE id = ?
     LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    http_response_code(404);
    exit('Usuário não encontrado.');
}

$erro = '';
$nome = (string)$usuario['nome'];
$email = (string)$usuario['email'];
$nomeUsuario = (string)$usuario['nome_usuario'];
$matricula = (string)($usuario['matricula'] ?? '');
$perfil = (string)$usuario['perfil'];
$situacao = (string)$usuario['situacao'];
$emailVerificado = !empty($usuario['email_verificado_em']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $nome = trim((string)($_POST['nome'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $cpfNovo = preg_replace('/\D/', '', (string)($_POST['cpf'] ?? ''));
    $nomeUsuario = trim((string)($_POST['nome_usuario'] ?? ''));
    $matricula = trim((string)($_POST['matricula'] ?? ''));
    $perfil = (string)($_POST['perfil'] ?? 'usuario');
    $situacao = (string)($_POST['situacao'] ?? 'aprovado');
    $emailVerificado = isset($_POST['email_verificado']);
    $novaSenha = (string)($_POST['nova_senha'] ?? '');
    $confirmacaoSenha = (string)($_POST['confirmacao_senha'] ?? '');

    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 120) {
        $erro = 'Informe um nome válido, com até 120 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $erro = 'Informe um e-mail válido.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $nomeUsuario)) {
        $erro = 'O nome de usuário deve ter de 3 a 60 caracteres, sem espaços.';
    } elseif ($matricula !== '' && mb_strlen($matricula) > 50) {
        $erro = 'A matrícula deve ter no máximo 50 caracteres.';
    } elseif ($cpfNovo !== '' && strlen($cpfNovo) !== 11) {
        $erro = 'O novo CPF deve conter 11 dígitos.';
    } elseif (!in_array($perfil, ['usuario', 'admin'], true)) {
        $erro = 'Perfil inválido.';
    } elseif (!in_array($situacao, ['pendente', 'aprovado', 'bloqueado'], true)) {
        $erro = 'Situação inválida.';
    } elseif ($novaSenha !== '' && strlen($novaSenha) < 8) {
        $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    } elseif ($novaSenha !== $confirmacaoSenha) {
        $erro = 'A confirmação da nova senha não coincide.';
    } elseif ($id === (int)$_SESSION['usuario_id'] && $situacao !== 'aprovado') {
        $erro = 'Você não pode bloquear ou deixar pendente a própria conta.';
    }

    if ($erro === '') {
        $verificarUnico = static function (mysqli $conn, string $campo, string $valor, int $id): bool {
            $permitidos = ['email', 'nome_usuario', 'matricula', 'cpf_hash'];
            if (!in_array($campo, $permitidos, true)) {
                throw new InvalidArgumentException('Campo de validação inválido.');
            }
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE {$campo} = ? AND id <> ? LIMIT 1");
            $stmt->bind_param('si', $valor, $id);
            $stmt->execute();
            return (bool)$stmt->get_result()->fetch_assoc();
        };

        if ($verificarUnico($conn, 'email', $email, $id)) {
            $erro = 'Este e-mail já pertence a outro usuário.';
        } elseif ($verificarUnico($conn, 'nome_usuario', $nomeUsuario, $id)) {
            $erro = 'Este nome de usuário já está em uso.';
        } elseif ($matricula !== '' && $verificarUnico($conn, 'matricula', $matricula, $id)) {
            $erro = 'Esta matrícula já pertence a outro usuário.';
        }

        $cpfCriptografado = $usuario['cpf'];
        $cpfHash = $usuario['cpf_hash'];
        if ($erro === '' && $cpfNovo !== '') {
            $cpfHashNovo = hashCpf($cpfNovo);
            if ($verificarUnico($conn, 'cpf_hash', $cpfHashNovo, $id)) {
                $erro = 'Este CPF já pertence a outro usuário.';
            } else {
                $cpfCriptografado = criptografarCpf($cpfNovo);
                $cpfHash = $cpfHashNovo;
            }
        }

        if ($erro === '' && $usuario['perfil'] === 'admin' && $perfil !== 'admin') {
            $totalAdmins = (int)$conn->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin'")->fetch_row()[0];
            if ($totalAdmins <= 1) {
                $erro = 'O sistema precisa manter pelo menos uma conta administradora.';
            }
        }
    }

    if ($erro === '') {
        $matriculaBanco = $matricula !== '' ? $matricula : null;
        $tentativas = $situacao === 'bloqueado' ? 5 : 0;
        $emailVerificadoEm = $emailVerificado
            ? ($usuario['email_verificado_em'] ?: date('Y-m-d H:i:s'))
            : null;

        $camposAlterados = [];
        foreach ([
            'nome' => $nome,
            'email' => $email,
            'nome_usuario' => $nomeUsuario,
            'matricula' => $matriculaBanco,
            'perfil' => $perfil,
            'situacao' => $situacao,
            'email_verificado_em' => $emailVerificadoEm,
        ] as $campo => $valor) {
            if (($usuario[$campo] ?? null) != $valor) {
                $camposAlterados[] = $campo;
            }
        }
        if ($cpfNovo !== '') $camposAlterados[] = 'cpf';
        if ($novaSenha !== '') $camposAlterados[] = 'senha';

        $conn->begin_transaction();
        try {
            if ($novaSenha !== '') {
                $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "UPDATE usuarios
                     SET nome=?, email=?, cpf=?, cpf_hash=?, nome_usuario=?, matricula=?, perfil=?, situacao=?,
                         tentativas_login=?, bloqueado_em=IF(?='bloqueado', NOW(), NULL),
                         email_verificado_em=?, senha=?
                     WHERE id=?"
                );
                $stmt->bind_param(
                    'ssssssssisssi',
                    $nome, $email, $cpfCriptografado, $cpfHash, $nomeUsuario, $matriculaBanco,
                    $perfil, $situacao, $tentativas, $situacao, $emailVerificadoEm, $senhaHash, $id
                );
                $stmt->execute();

                $stmt = $conn->prepare(
                    'UPDATE recuperacoes_senha SET usado_em = NOW() WHERE usuario_id = ? AND usado_em IS NULL'
                );
                $stmt->bind_param('i', $id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare(
                    "UPDATE usuarios
                     SET nome=?, email=?, cpf=?, cpf_hash=?, nome_usuario=?, matricula=?, perfil=?, situacao=?,
                         tentativas_login=?, bloqueado_em=IF(?='bloqueado', NOW(), NULL),
                         email_verificado_em=?
                     WHERE id=?"
                );
                $stmt->bind_param(
                    'ssssssssissi',
                    $nome, $email, $cpfCriptografado, $cpfHash, $nomeUsuario, $matriculaBanco,
                    $perfil, $situacao, $tentativas, $situacao, $emailVerificadoEm, $id
                );
                $stmt->execute();
            }

            registrarAuditoria('admin_editou_usuario', 'usuario', $id, [
                'campos_alterados' => array_values(array_unique($camposAlterados)),
            ]);
            $conn->commit();

            if ($id === (int)$_SESSION['usuario_id']) {
                $_SESSION['usuario_nome'] = $nome;
                $_SESSION['usuario_email'] = $email;
                $_SESSION['usuario_perfil'] = $perfil;
            }

            $destino = $id === (int)$_SESSION['usuario_id'] && $perfil !== 'admin'
                ? 'dashboard.php'
                : 'admin_usuarios.php?ok=editar';
            header('Location: ' . $destino);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('Falha ao editar usuário: ' . $e->getMessage());
            $erro = 'Não foi possível salvar as alterações. Verifique os dados e tente novamente.';
        }
    }
}

$cpfMascarado = mascararCpf($usuario['cpf']);
$titulo = 'Editar usuário';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <h1>Editar usuário</h1>
        <p>Altere os dados, o acesso e a senha da conta.</p>
    </div>
    <a class="btn btn-ghost" href="admin_usuarios.php">Voltar</a>
</section>

<?php if ($erro): ?><div class="alert alert-danger alert-page"><?= e($erro) ?></div><?php endif; ?>

<section class="panel form-panel">
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <label class="col-2">Nome completo *
            <input type="text" name="nome" required maxlength="120" autocomplete="name" value="<?= e($nome) ?>">
        </label>

        <label>E-mail *
            <input type="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($email) ?>">
        </label>

        <label>Nome de usuário *
            <input type="text" name="nome_usuario" required maxlength="60" autocomplete="username" value="<?= e($nomeUsuario) ?>">
        </label>

        <label>Matrícula
            <input type="text" name="matricula" maxlength="50" value="<?= e($matricula) ?>">
        </label>

        <label>Novo CPF
            <input type="text" name="cpf" inputmode="numeric" maxlength="14" placeholder="Deixe vazio para manter <?= e($cpfMascarado) ?>">
        </label>

        <label>Perfil
            <select name="perfil">
                <option value="usuario" <?= $perfil === 'usuario' ? 'selected' : '' ?>>Usuário</option>
                <option value="admin" <?= $perfil === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </label>

        <label>Situação
            <select name="situacao">
                <option value="pendente" <?= $situacao === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                <option value="aprovado" <?= $situacao === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                <option value="bloqueado" <?= $situacao === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
            </select>
        </label>

        <label class="checkbox-card col-2">
            <input type="checkbox" name="email_verificado" value="1" <?= $emailVerificado ? 'checked' : '' ?>>
            <span><strong>E-mail verificado</strong><small>Desmarque para exigir nova confirmação antes do próximo acesso.</small></span>
        </label>

        <label>Nova senha
            <input type="password" name="nova_senha" minlength="8" autocomplete="new-password" placeholder="Deixe vazio para manter a atual">
        </label>

        <label>Confirmar nova senha
            <input type="password" name="confirmacao_senha" minlength="8" autocomplete="new-password">
        </label>

        <div class="form-actions col-2">
            <a href="admin_usuarios.php" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
