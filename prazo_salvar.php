<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();
validarCsrf();

$id = (int)($_POST['id'] ?? 0);
$numero = trim($_POST['numero_processo'] ?? '');
$assunto = trim($_POST['assunto'] ?? '');
$procuradores = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['procuradores'] ?? [])))));
$dataEntrada = $_POST['data_entrada'] ?? '';
$dataVencimento = $_POST['data_vencimento'] ?? '';
$status = $_POST['status'] ?? 'Novo';
$observacoes = trim($_POST['observacoes'] ?? '');

$statusPermitidos = ['Novo', 'Em andamento', 'Concluído'];

if ($numero === '' || $assunto === '' || $dataEntrada === '' || $dataVencimento === '' || !$procuradores || !in_array($status, $statusPermitidos, true)) {
    exit('Dados inválidos.');
}

$conn = db();
$novoProcesso = $id <= 0;
$marcas = implode(',', array_fill(0, count($procuradores), '?'));
$stmtValidar = $conn->prepare("SELECT COUNT(*) total FROM procuradores WHERE ativo = 1 AND id IN ($marcas)");
$tipos = str_repeat('i', count($procuradores)); $stmtValidar->bind_param($tipos, ...$procuradores); $stmtValidar->execute();
if ((int)$stmtValidar->get_result()->fetch_assoc()['total'] !== count($procuradores)) exit('Um dos procuradores selecionados é inválido.');

$conn->begin_transaction();
try {

if ($id > 0) {
    $stmt = $conn->prepare("
        UPDATE prazos
        SET numero_processo = ?, assunto = ?, responsavel_id = NULL, data_entrada = ?, data_vencimento = ?, status = ?, observacoes = ?
        WHERE id = ?
    ");
    $stmt->bind_param('ssssssi', $numero, $assunto, $dataEntrada, $dataVencimento, $status, $observacoes, $id);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        INSERT INTO prazos (numero_processo, assunto, responsavel_id, data_entrada, data_vencimento, status, observacoes, criado_por)
        VALUES (?, ?, NULL, ?, ?, ?, ?, ?)
    ");
    $criadoPor = (int)$_SESSION['usuario_id'];
    $stmt->bind_param('ssssssi', $numero, $assunto, $dataEntrada, $dataVencimento, $status, $observacoes, $criadoPor);
    $stmt->execute();
    $id = $conn->insert_id;
}

$stmt = $conn->prepare('DELETE FROM prazo_procuradores WHERE prazo_id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
$stmt = $conn->prepare('INSERT INTO prazo_procuradores (prazo_id, procurador_id) VALUES (?, ?)');
foreach ($procuradores as $procuradorId) { $stmt->bind_param('ii', $id, $procuradorId); $stmt->execute(); }
$conn->commit();
registrarAuditoria($novoProcesso ? 'processo_criado' : 'processo_salvo','processo',$id,['numero'=>$numero,'status'=>$status]);
} catch (Throwable $e) { $conn->rollback(); throw $e; }

header('Location: prazos.php');
exit;
