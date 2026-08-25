<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/prazos_repository.php';

exigirLogin();

$busca = trim($_GET['busca'] ?? '');
$filtro = $_GET['filtro'] ?? 'todos';
$prazos = listarPrazos($busca, $filtro);

$titulo = 'Prazos';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <h1>Prazos</h1>
        <p>Cadastre processos, vincule procuradores e acompanhe os vencimentos.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-pdf" type="button" data-open-export data-export-format="pdf">Exportar PDF</button>
        <button class="btn btn-excel" type="button" data-open-export data-export-format="excel">Exportar Excel</button>
        <a href="prazo_form.php" class="btn btn-primary">+ Novo processo</a>
    </div>
</section>

<?php if (isset($_GET['observacao'])): ?>
    <div class="alert alert-success alert-page">Observações salvas com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form class="filters" method="get">
        <input type="search" name="busca" value="<?= e($busca) ?>" placeholder="Pesquisar processo, assunto ou procurador">
        <select name="filtro">
            <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="novos" <?= $filtro === 'novos' ? 'selected' : '' ?>>Novos</option>
            <option value="vencidos" <?= $filtro === 'vencidos' ? 'selected' : '' ?>>Vencidos</option>
            <option value="hoje" <?= $filtro === 'hoje' ? 'selected' : '' ?>>Vencem hoje</option>
            <option value="cinco_dias" <?= $filtro === 'cinco_dias' ? 'selected' : '' ?>>Próximos 5 dias</option>
            <option value="trinta_dias" <?= $filtro === 'trinta_dias' ? 'selected' : '' ?>>De 6 a 30 dias</option>
            <option value="proximos" <?= $filtro === 'proximos' ? 'selected' : '' ?>>Próximos 1 a 30 dias</option>
            <option value="em_dia" <?= $filtro === 'em_dia' ? 'selected' : '' ?>>Em dia</option>
            <option value="concluidos" <?= $filtro === 'concluidos' ? 'selected' : '' ?>>Concluídos</option>
        </select>
        <button class="btn" type="submit">Filtrar</button>
        <a class="btn btn-ghost" href="prazos.php">Limpar</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Processo</th>
                <th>Assunto</th>
                <th>Entrada</th>
                <th>Procuradores vinculados</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$prazos): ?>
                <tr><td colspan="8" class="empty">Nenhum prazo encontrado.</td></tr>
            <?php endif; ?>

            <?php foreach ($prazos as $prazo): ?>
                <?php $sit = statusPrazo($prazo['data_vencimento'], $prazo['status']); ?>
                <tr>
                    <td><strong><?= e($prazo['numero_processo']) ?></strong></td>
                    <td><?= e($prazo['assunto']) ?></td>
                    <td class="cell-nowrap"><?= dataBr($prazo['data_entrada']) ?></td>
                    <td><?= e($prazo['procuradores_nomes'] ?: 'Não vinculado') ?></td>
                    <td class="cell-nowrap"><?= dataBr($prazo['data_vencimento']) ?></td>
                    <td class="cell-nowrap"><?= e($prazo['status']) ?></td>
                    <td><span class="status <?= $sit['classe'] ?>"><?= e($sit['texto']) ?></span></td>
                    <td class="actions">
                        <a class="link" href="prazo_form.php?id=<?= (int)$prazo['id'] ?>">Editar</a>

                        <?php if ($prazo['status'] !== 'Concluído'): ?>
                            <button
                                class="link-button note"
                                type="button"
                                data-open-note
                                data-id="<?= (int)$prazo['id'] ?>"
                                data-processo="<?= e($prazo['numero_processo']) ?>"
                                data-observacao="<?= e($prazo['observacoes'] ?? '') ?>"
                            >Observações</button>

                            <form method="post" action="prazo_status.php" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$prazo['id'] ?>">
                                <input type="hidden" name="status" value="Concluído">
                                <button type="submit" class="link-button success">Concluir</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="prazo_excluir.php" class="inline-form" onsubmit="return confirm('Excluir este prazo?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$prazo['id'] ?>">
                            <button type="submit" class="link-button danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php $retornoObservacao = 'prazos.php?' . http_build_query(['busca' => $busca, 'filtro' => $filtro]); ?>
<?php require __DIR__ . '/includes/modal_exportacao.php'; ?>
<?php require __DIR__ . '/includes/modal_observacao.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
