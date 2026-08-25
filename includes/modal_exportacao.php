<?php
$modalFiltro = $filtro ?? 'todos';
$modalBusca = $busca ?? '';
?>
<dialog class="export-modal" id="export-modal" aria-labelledby="export-modal-title">
    <div class="modal-head">
        <div>
            <h2 id="export-modal-title">Exportar prazos</h2>
            <p>Selecione somente os registros que deseja incluir.</p>
        </div>
        <button class="modal-close" type="button" data-close-export aria-label="Fechar">×</button>
    </div>

    <form class="modal-form" id="export-form" method="get">
        <label>Situação
            <select name="filtro">
                <option value="todos" <?= $modalFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="novos" <?= $modalFiltro === 'novos' ? 'selected' : '' ?>>Novos</option>
                <option value="vencidos" <?= $modalFiltro === 'vencidos' ? 'selected' : '' ?>>Vencidos</option>
                <option value="hoje" <?= $modalFiltro === 'hoje' ? 'selected' : '' ?>>Vencem hoje</option>
                <option value="cinco_dias" <?= $modalFiltro === 'cinco_dias' ? 'selected' : '' ?>>Próximos 5 dias</option>
                <option value="trinta_dias" <?= $modalFiltro === 'trinta_dias' ? 'selected' : '' ?>>De 6 a 30 dias</option>
                <option value="em_dia" <?= $modalFiltro === 'em_dia' ? 'selected' : '' ?>>Em dia</option>
                <option value="concluidos" <?= $modalFiltro === 'concluidos' ? 'selected' : '' ?>>Concluídos</option>
            </select>
        </label>

        <label>Pesquisar <span class="optional">(opcional)</span>
            <input type="search" name="busca" value="<?= e($modalBusca) ?>" placeholder="Processo, assunto ou responsável">
        </label>

        <div class="modal-date-grid">
            <label>Vencimento a partir de <span class="optional">(opcional)</span>
                <input type="date" name="data_inicio">
            </label>
            <label>Vencimento até <span class="optional">(opcional)</span>
                <input type="date" name="data_fim">
            </label>
        </div>

        <div class="modal-actions">
            <button class="btn btn-ghost" type="button" data-close-export>Cancelar</button>
            <button class="btn btn-primary" id="export-submit" type="submit">Baixar arquivo</button>
        </div>
    </form>
</dialog>
<script src="assets/js/exportacao.js"></script>
