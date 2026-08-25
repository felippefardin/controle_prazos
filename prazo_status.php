<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();
validarCsrf();

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id <= 0 || !in_array($status, ['Novo', 'Em andamento', 'Concluído'], true)) {
    exit('Dados inválidos.');
}

$stmt = db()->prepare("UPDATE prazos SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $id);
$stmt->execute();
registrarAuditoria('status_processo_alterado','processo',$id,['status'=>$status]);

header('Location: ' . ($status === 'Concluído' ? 'concluidos.php' : 'prazos.php'));
exit;
