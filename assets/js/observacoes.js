(() => {
    const modal = document.getElementById('note-modal');
    const id = document.getElementById('note-id');
    const text = document.getElementById('note-text');
    const count = document.getElementById('note-count');
    const processo = document.getElementById('note-processo');

    if (!modal || !id || !text || !count || !processo) return;

    const atualizarContador = () => { count.textContent = String(text.value.length); };
    text.addEventListener('input', atualizarContador);

    document.querySelectorAll('[data-open-note]').forEach((button) => {
        button.addEventListener('click', () => {
            id.value = button.dataset.id || '';
            text.value = button.dataset.observacao || '';
            processo.textContent = 'Processo ' + (button.dataset.processo || '');
            atualizarContador();
            modal.showModal();
            text.focus();
        });
    });

    document.querySelectorAll('[data-close-note]').forEach((button) => {
        button.addEventListener('click', () => modal.close());
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });
})();
