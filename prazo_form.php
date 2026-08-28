<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();

$conn = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$procuradores = $conn->query("SELECT id, nome, oab FROM procuradores WHERE ativo = 1 ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$procuradoresVinculados = [];

$dados = [
    'id' => 0,
    'numero_processo' => '',
    'assunto' => '',
    'data_entrada' => date('Y-m-d'),
    'data_vencimento' => '',
    'status' => 'Novo',
    'observacoes' => '',
];

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM prazos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();

    if (!$registro) {
        http_response_code(404);
        exit('Prazo não encontrado.');
    }

    $dados = $registro;
    $stmt = $conn->prepare('SELECT procurador_id FROM prazo_procuradores WHERE prazo_id = ?');
    $stmt->bind_param('i', $id); $stmt->execute();
    $procuradoresVinculados = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'procurador_id'));
}

$titulo = $id ? 'Editar processo' : 'Novo processo';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <h1><?= $id ? 'Editar processo' : 'Novo processo' ?></h1>
        <p>Informe os dados do processo e vincule um ou mais procuradores.</p>
    </div>
</section>

<section class="panel form-panel">
    <form method="post" action="prazo_salvar.php" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

        <label class="col-2">
            Número do processo *
            <input type="text" name="numero_processo" required value="<?= e($dados['numero_processo']) ?>" placeholder="Ex.: 0001234-25.2026.8.08.0000">
        </label>

        <label class="col-2">
            Assunto / descrição *
            <input type="text" name="assunto" required value="<?= e($dados['assunto']) ?>" placeholder="Ex.: Prazo para manifestação">
        </label>

        <label>
            Status
            <select name="status">
                <?php foreach (['Novo', 'Em andamento', 'Concluído', 'Retorno'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= $dados['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <fieldset class="procuradores-field col-2">
            <legend>Procuradores vinculados *</legend>
            <?php if (!$procuradores): ?>
                <p class="field-help">Nenhum procurador ativo. <a href="procurador_form.php">Cadastre um procurador primeiro.</a></p>
            <?php else: ?>
                <div class="checkbox-grid">
                <?php foreach ($procuradores as $p): ?>
                    <label class="checkbox-card"><input type="checkbox" name="procuradores[]" value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $procuradoresVinculados, true) ? 'checked' : '' ?>>
                        <span><strong><?= e($p['nome']) ?></strong><small><?= e($p['oab'] ?: 'OAB não informada') ?></small></span>
                    </label>
                <?php endforeach; ?>
                </div>
                <p class="field-help">Selecione pelo menos um. Você pode marcar vários procuradores.</p>
            <?php endif; ?>
        </fieldset>

        <label>
            Data de entrada *
            <input type="date" name="data_entrada" required value="<?= e($dados['data_entrada']) ?>">
        </label>

        <label>
            Data de vencimento *
            <input type="date" name="data_vencimento" required value="<?= e($dados['data_vencimento']) ?>">
        </label>

        <label class="col-2">
            Observações
            <textarea name="observacoes" rows="5" placeholder="Informações adicionais sobre o prazo"><?= e($dados['observacoes']) ?></textarea>
        </label>

        <div class="form-actions col-2">
            <a href="prazos.php" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary" <?= !$procuradores ? 'disabled' : '' ?>>Salvar processo</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
