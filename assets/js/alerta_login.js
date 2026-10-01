(() => {
    const modal = document.getElementById('login-deadline-modal');
    if (!modal) return;

    const close = () => {
        if (modal.open) modal.close();
    };

    modal.querySelectorAll('[data-close-deadline]').forEach(button => {
        button.addEventListener('click', close);
    });

    // Não restaura um aviso aberto ao voltar pelo histórico do navegador (bfcache).
    window.addEventListener('pagehide', close);
    window.addEventListener('pageshow', event => {
        if (event.persisted) close();
    });
    modal.querySelectorAll('[data-view-deadline]').forEach(link => {
        link.addEventListener('click', event => {
            // Ctrl/Cmd+clique mantém a navegação normal para uma nova aba.
            if (!event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) close();
        });
    });

    modal.showModal();
})();
