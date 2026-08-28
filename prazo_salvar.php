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

$statusPermitidos = ['Novo', 'Em andamento', 'Concluído', 'Retorno'];

if ($numero === '' || $assunto === '' || $dataEntrada === '' || $dataVencimento === '' || !$procuradores || !in_array($status, $statusPermitidos, true)) {
    exit('Dados inválidos.');
}

$conn = db();
$novoProcesso = $id <= 0;
$antes = null;
if (!$novoProcesso) {
    $qAntes=$conn->prepare('SELECT numero_processo,assunto,data_entrada,data_vencimento,status,observacoes FROM prazos WHERE id=?');$qAntes->bind_param('i',$id);$qAntes->execute();$antes=$qAntes->get_result()->fetch_assoc();
    if(!$antes){http_response_code(404);exit('Processo não encontrado.');}
    $qProc=$conn->prepare('SELECT pr.nome FROM prazo_procuradores pp JOIN procuradores pr ON pr.id=pp.procurador_id WHERE pp.prazo_id=? ORDER BY pr.nome');$qProc->bind_param('i',$id);$qProc->execute();$antes['procuradores']=array_column($qProc->get_result()->fetch_all(MYSQLI_ASSOC),'nome');
}
$marcas = implode(',', array_fill(0, count($procuradores), '?'));
$stmtValidar = $conn->prepare("SELECT id,nome FROM procuradores WHERE ativo = 1 AND id IN ($marcas) ORDER BY nome");
$tipos = str_repeat('i', count($procuradores)); $stmtValidar->bind_param($tipos, ...$procuradores); $stmtValidar->execute();
$procuradoresValidos=$stmtValidar->get_result()->fetch_all(MYSQLI_ASSOC);if(count($procuradoresValidos)!==count($procuradores)) exit('Um dos procuradores selecionados é inválido.');
$nomesProcuradores=array_column($procuradoresValidos,'nome');

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
$depois=['numero_processo'=>$numero,'assunto'=>$assunto,'data_entrada'=>$dataEntrada,'data_vencimento'=>$dataVencimento,'status'=>$status,'observacoes'=>$observacoes,'procuradores'=>$nomesProcuradores];
if($novoProcesso){registrarAuditoria('processo_criado','processo',$id,['dados'=>$depois]);}
else{$mudancas=[];foreach($depois as $campo=>$novoValor){$antigo=$antes[$campo]??null;if($campo==='procuradores'){sort($antigo);sort($novoValor);}if($antigo!=$novoValor)$mudancas[$campo]=['antes'=>$antigo,'depois'=>$novoValor];}registrarAuditoria('processo_editado','processo',$id,['mudancas'=>$mudancas]);}
} catch (Throwable $e) { $conn->rollback(); throw $e; }

header('Location: prazos.php');
exit;
