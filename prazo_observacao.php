<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();
validarCsrf();

$id = (int)($_POST['id'] ?? 0);
$observacoes = trim($_POST['observacoes'] ?? '');
$retorno = $_POST['retorno'] ?? 'prazos.php';

if ($id <= 0 || mb_strlen($observacoes, 'UTF-8') > 5000) {
    http_response_code(422);
    exit('Observação inválida. Use no máximo 5.000 caracteres.');
}

$consulta=db()->prepare('SELECT observacoes FROM prazos WHERE id=?');$consulta->bind_param('i',$id);$consulta->execute();$observacaoAnterior=$consulta->get_result()->fetch_assoc()['observacoes']??null;
$stmt = db()->prepare("UPDATE prazos SET observacoes = ? WHERE id = ? AND status <> 'Concluído'");
$stmt->bind_param('si', $observacoes, $id);
$stmt->execute();
registrarAuditoria('observacao_processo_alterada','processo',$id,['mudancas'=>['observacoes'=>['antes'=>$observacaoAnterior,'depois'=>$observacoes]]]);

$partes = parse_url($retorno);
$pagina = basename($partes['path'] ?? 'prazos.php');
if (!in_array($pagina, ['dashboard.php', 'prazos.php'], true)) {
    $pagina = 'prazos.php';
}
$destino = $pagina;
if (!empty($partes['query'])) {
    $destino .= '?' . $partes['query'];
}
$destino .= str_contains($destino, '?') ? '&observacao=salva' : '?observacao=salva';

header('Location: ' . $destino);
exit;
