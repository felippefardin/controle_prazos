<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();
validarCsrf();

$prazoId = (int)($_POST['prazo_id'] ?? 0);
$comentario = trim((string)($_POST['comentario'] ?? ''));
if ($prazoId <= 0 || $comentario === '' || mb_strlen($comentario) > 5000) {
    http_response_code(422);
    exit('Comentário inválido. Use no máximo 5.000 caracteres.');
}

$conn = db();
$q = $conn->prepare('SELECT id FROM prazos WHERE id = ?');
$q->bind_param('i', $prazoId);
$q->execute();
if (!$q->get_result()->fetch_assoc()) {
    http_response_code(404);
    exit('Processo não encontrado.');
}

$usuarioId = (int)$_SESSION['usuario_id'];
$stmt = $conn->prepare('INSERT INTO processo_comentarios (prazo_id, usuario_id, comentario) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $prazoId, $usuarioId, $comentario);
$stmt->execute();
registrarAuditoria('comentario_processo_adicionado', 'processo', $prazoId, ['comentario_id' => $conn->insert_id]);

header('Location: processo_visualizar.php?id=' . $prazoId . '&comentario=ok#comentarios');
exit;
