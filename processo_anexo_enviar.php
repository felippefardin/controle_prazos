<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();
validarCsrf();

$prazoId = (int)($_POST['prazo_id'] ?? 0);
$arquivo = $_FILES['anexo'] ?? null;
if ($prazoId <= 0 || !is_array($arquivo) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(422);
    exit('Não foi possível receber o arquivo.');
}

$conn = db();
$q = $conn->prepare('SELECT id FROM prazos WHERE id = ?');
$q->bind_param('i', $prazoId);
$q->execute();
if (!$q->get_result()->fetch_assoc()) {
    http_response_code(404);
    exit('Processo não encontrado.');
}

$tamanho = (int)($arquivo['size'] ?? 0);
if ($tamanho <= 0 || $tamanho > 10 * 1024 * 1024) {
    http_response_code(422);
    exit('O arquivo deve ter no máximo 10 MB.');
}

$nomeOriginal = basename(str_replace('\\', '/', (string)$arquivo['name']));
$nomeOriginal = preg_replace('/[\x00-\x1F\x7F]/u', '', $nomeOriginal) ?: 'arquivo';
$nomeOriginal = mb_substr($nomeOriginal, 0, 255);
$extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
$tiposPermitidos = [
    'pdf' => ['application/pdf'],
    'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
    'png' => ['image/png'], 'webp' => ['image/webp'],
    'doc' => ['application/msword', 'application/CDFV2'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xls' => ['application/vnd.ms-excel', 'application/CDFV2'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
];
$mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$arquivo['tmp_name']) ?: 'application/octet-stream';
if (!isset($tiposPermitidos[$extensao]) || !in_array($mime, $tiposPermitidos[$extensao], true)) {
    http_response_code(422);
    exit('Tipo de arquivo não permitido. Envie PDF, imagem, Word ou Excel.');
}

$diretorio = __DIR__ . '/storage/anexos';
if (!is_dir($diretorio) && !mkdir($diretorio, 0750, true) && !is_dir($diretorio)) {
    throw new RuntimeException('Não foi possível preparar o armazenamento de anexos.');
}
$nomeArmazenado = bin2hex(random_bytes(32));
$destino = $diretorio . '/' . $nomeArmazenado;
if (!move_uploaded_file((string)$arquivo['tmp_name'], $destino)) {
    throw new RuntimeException('Não foi possível armazenar o anexo.');
}

try {
    $usuarioId = (int)$_SESSION['usuario_id'];
    $stmt = $conn->prepare('INSERT INTO processo_anexos (prazo_id, usuario_id, nome_original, nome_armazenado, mime_type, tamanho) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iisssi', $prazoId, $usuarioId, $nomeOriginal, $nomeArmazenado, $mime, $tamanho);
    $stmt->execute();
    registrarAuditoria('anexo_processo_adicionado', 'processo', $prazoId, ['anexo_id' => $conn->insert_id, 'nome' => $nomeOriginal]);
} catch (Throwable $e) {
    if (is_file($destino)) unlink($destino);
    throw $e;
}

header('Location: processo_visualizar.php?id=' . $prazoId . '&anexo=ok#anexos');
exit;
