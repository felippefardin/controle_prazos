<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/prazos_repository.php';

exigirLogin();

$busca = trim($_GET['busca'] ?? '');
$filtro = 'concluidos';
$prazos = listarPrazos($busca, $filtro);

$titulo = 'Processos concluídos';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <h1>Processos concluídos</h1>
        <p>Histórico dos processos retirados do Dashboard após a conclusão.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-pdf" type="button" data-open-export data-export-format="pdf">Exportar PDF</button>
        <button class="btn btn-excel" type="button" data-open-export data-export-format="excel">Exportar Excel</button>
    </div>
</section>

<section class="panel">
    <form class="completed-filters" method="get">
        <input type="search" name="busca" value="<?= e($busca) ?>" placeholder="Pesquisar processo, assunto ou procurador">
        <button class="btn" type="submit">Pesquisar</button>
        <a class="btn btn-ghost" href="concluidos.php">Limpar</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Processo</th>
                <th>Assunto</th>
                <th>Procuradores vinculados</th>
                <th>Entrada</th>
                <th>Vencimento</th>
                <th>Observações</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$prazos): ?>
                <tr><td colspan="7" class="empty">Nenhum processo concluído encontrado.</td></tr>
            <?php endif; ?>

            <?php foreach ($prazos as $prazo): ?>
                <tr>
                    <td><strong><?= e($prazo['numero_processo']) ?></strong></td>
                    <td><?= e($prazo['assunto']) ?></td>
                    <td><?= e($prazo['procuradores_nomes'] ?: 'Não vinculado') ?></td>
                    <td class="cell-nowrap"><?= dataBr($prazo['data_entrada']) ?></td>
                    <td class="cell-nowrap"><?= dataBr($prazo['data_vencimento']) ?></td>
                    <td class="notes-cell"><?= nl2br(e($prazo['observacoes'] ?: 'Sem observações')) ?></td>
                    <td class="actions"><a class="link" href="prazo_form.php?id=<?= (int)$prazo['id'] ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/modal_exportacao.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
