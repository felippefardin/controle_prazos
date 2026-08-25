<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
exigirLogin();
$id = (int)($_GET['id'] ?? 0);
$dados = ['id'=>0, 'nome'=>'', 'oab'=>'', 'email'=>'', 'telefone'=>'', 'ativo'=>1];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM procuradores WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
    $dados = $stmt->get_result()->fetch_assoc() ?: exit('Procurador não encontrado.');
}
$titulo = $id ? 'Editar procurador' : 'Cadastrar procurador';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head"><div><h1><?= $id ? 'Editar procurador' : 'Cadastrar procurador' ?></h1><p>Esta página é exclusiva para o cadastro de procuradores.</p></div></section>
<section class="panel form-panel"><form method="post" action="procurador_salvar.php" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">
    <label class="col-2">Nome completo *<input name="nome" required maxlength="150" value="<?= e($dados['nome']) ?>"></label>
    <label>Número da OAB<input name="oab" maxlength="50" value="<?= e($dados['oab']) ?>" placeholder="Ex.: OAB/ES 12345"></label>
    <label>Telefone<input name="telefone" maxlength="30" value="<?= e($dados['telefone']) ?>"></label>
    <label class="col-2">E-mail<input type="email" name="email" maxlength="190" value="<?= e($dados['email']) ?>"></label>
    <label class="check-inline col-2"><input type="checkbox" name="ativo" value="1" <?= $dados['ativo'] ? 'checked' : '' ?>> Procurador ativo</label>
    <div class="form-actions col-2"><a class="btn btn-ghost" href="procuradores.php">Cancelar</a><button class="btn btn-primary">Salvar procurador</button></div>
</form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
