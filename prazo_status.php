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

$consulta=db()->prepare('SELECT status FROM prazos WHERE id=?');$consulta->bind_param('i',$id);$consulta->execute();$statusAnterior=$consulta->get_result()->fetch_assoc()['status']??null;
$stmt = db()->prepare("UPDATE prazos SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $id);
$stmt->execute();
registrarAuditoria($status==='Concluído'?'processo_concluido':'status_processo_alterado','processo',$id,['mudancas'=>['status'=>['antes'=>$statusAnterior,'depois'=>$status]]]);

header('Location: ' . ($status === 'Concluído' ? 'concluidos.php' : 'prazos.php'));
exit;
