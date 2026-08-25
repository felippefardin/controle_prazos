<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();
validarCsrf();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    exit('Prazo inválido.');
}

$stmt = db()->prepare("DELETE FROM prazos WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
registrarAuditoria('processo_excluido','processo',$id);

header('Location: prazos.php');
exit;
