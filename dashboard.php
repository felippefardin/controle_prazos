<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

exigirLogin();

$conn = db();

$cards = [
    'concluidos' => 0,
    'andamento' => 0,
    'proximos' => 0,
];

$sqlCards = "
SELECT
    SUM(status = 'Concluído') AS concluidos,
    SUM(status <> 'Concluído') AS andamento,
    SUM(status <> 'Concluído' AND DATEDIFF(data_vencimento, CURDATE()) BETWEEN 1 AND 30) AS proximos
FROM prazos
";
$res = $conn->query($sqlCards)->fetch_assoc();
foreach ($cards as $k => $_) {
    $cards[$k] = (int)($res[$k] ?? 0);
}

$proximos = $conn->query("SELECT p.*, proc.procuradores_nomes, DATEDIFF(p.data_vencimento,CURDATE()) dias_restantes FROM prazos p LEFT JOIN (SELECT pp.prazo_id,GROUP_CONCAT(pr.nome ORDER BY pr.nome SEPARATOR ', ') procuradores_nomes FROM prazo_procuradores pp JOIN procuradores pr ON pr.id=pp.procurador_id GROUP BY pp.prazo_id) proc ON proc.prazo_id=p.id WHERE p.status<>'Concluído' AND DATEDIFF(p.data_vencimento,CURDATE()) BETWEEN 1 AND 30 ORDER BY p.data_vencimento,p.id")->fetch_all(MYSQLI_ASSOC);
$alertaCinco = array_filter($proximos, fn(array $p): bool => (int)$p['dias_restantes'] >= 1 && (int)$p['dias_restantes'] <= 5);

$titulo = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>Visão rápida dos prazos que exigem atenção.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-pdf" type="button" data-open-export data-export-format="pdf">Exportar PDF</button>
        <button class="btn btn-excel" type="button" data-open-export data-export-format="excel">Exportar Excel</button>
        <a href="prazo_form.php" class="btn btn-primary">+ Novo processo</a>
    </div>
</section>

<section class="cards cards-3">
    <a class="card card-green" href="concluidos.php"><span>Processos concluídos</span><strong><?= $cards['concluidos'] ?></strong></a>
    <a class="card card-blue" href="prazos.php"><span>Processos em andamento</span><strong><?= $cards['andamento'] ?></strong></a>
    <a class="card card-orange" href="prazos.php?filtro=proximos"><span>Próximos a vencer (1–30 dias)</span><strong><?= $cards['proximos'] ?></strong></a>
</section>

<section class="deadline-alerts" aria-label="Alertas de vencimento">
    <a class="deadline-alert alert-5 <?= $alertaCinco ? 'is-active' : '' ?>" href="prazos.php?filtro=cinco_dias" aria-label="Ver processos que vencem entre 1 e 5 dias"><strong>Alerta: de 1 a 5 dias</strong><span><?= count($alertaCinco) ?> processo(s)</span><small>Clique para abrir a lista</small></a>
</section>

<section class="panel deadline-panel"><div class="panel-head"><div><h2>Processos próximos a vencer</h2><p>Enumerados por ordem de vencimento, de 1 a 30 dias.</p></div></div><div class="table-wrap"><table>
<thead><tr><th>#</th><th>Processo</th><th>Assunto</th><th>Procuradores vinculados</th><th>Vencimento</th><th>Dias restantes</th></tr></thead><tbody>
<?php if (!$proximos): ?><tr><td colspan="6" class="empty">Nenhum processo vence nos próximos 30 dias.</td></tr><?php endif; ?>
<?php foreach ($proximos as $i=>$p): ?>
<?php $dias=(int)$p['dias_restantes']; $faixa=$dias<=10?'deadline-red':($dias<=20?'deadline-yellow':'deadline-green'); ?>
<tr class="<?= $faixa ?>"><td><?= $i+1 ?></td><td><a class="link" href="processo_visualizar.php?id=<?= (int)$p['id'] ?>"><?= e($p['numero_processo']) ?></a></td><td><?= e($p['assunto']) ?></td><td><?= e($p['procuradores_nomes'] ?: 'Não vinculado') ?></td><td><?= dataBr($p['data_vencimento']) ?></td><td><span class="deadline-days <?= $faixa ?>"><?= $dias ?> dia(s)</span></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>

<?php if (isset($_GET['observacao'])): ?>
    <div class="alert alert-success alert-page">Observações salvas com sucesso.</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/modal_exportacao.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
