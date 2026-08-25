<dialog class="export-modal note-modal" id="note-modal" aria-labelledby="note-modal-title">
    <div class="modal-head">
        <div>
            <h2 id="note-modal-title">Observações do processo</h2>
            <p id="note-processo">Registre informações importantes sobre o andamento.</p>
        </div>
        <button class="modal-close" type="button" data-close-note aria-label="Fechar">×</button>
    </div>

    <form class="modal-form" action="prazo_observacao.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" id="note-id">
        <input type="hidden" name="retorno" value="<?= e($retornoObservacao ?? 'prazos.php') ?>">
        <label>Observações
            <textarea name="observacoes" id="note-text" rows="8" maxlength="5000" placeholder="Digite aqui as observações sobre este processo..."></textarea>
        </label>
        <div class="note-counter"><span id="note-count">0</span>/5000 caracteres</div>
        <div class="modal-actions">
            <button class="btn btn-ghost" type="button" data-close-note>Cancelar</button>
            <button class="btn btn-primary" type="submit">Salvar observações</button>
        </div>
    </form>
</dialog>
<script src="assets/js/observacoes.js"></script>
