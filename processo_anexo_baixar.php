<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

exigirLogin();

$id = (int)($_GET['id'] ?? 0);
$q = db()->prepare('SELECT nome_original, nome_armazenado, mime_type, tamanho FROM processo_anexos WHERE id = ?');
$q->bind_param('i', $id);
$q->execute();
$anexo = $q->get_result()->fetch_assoc();
$caminho = $anexo ? __DIR__ . '/storage/anexos/' . $anexo['nome_armazenado'] : '';
if (!$anexo || !is_file($caminho)) {
    http_response_code(404);
    exit('Anexo não encontrado.');
}

$nomeSeguro = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$anexo['nome_original']) ?: 'anexo';
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $anexo['mime_type']);
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: attachment; filename="' . $nomeSeguro . '"; filename*=UTF-8\'\'' . rawurlencode((string)$anexo['nome_original']));
header('Cache-Control: private, no-store');
readfile($caminho);
exit;
