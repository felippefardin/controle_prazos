(() => {
    const modal = document.getElementById('export-modal');
    const form = document.getElementById('export-form');
    const submit = document.getElementById('export-submit');

    if (!modal || !form || !submit) return;

    document.querySelectorAll('[data-open-export]').forEach((button) => {
        button.addEventListener('click', () => {
            const excel = button.dataset.exportFormat === 'excel';
            form.action = excel ? 'exportar_excel.php' : 'exportar_pdf.php';
            submit.textContent = excel ? 'Baixar Excel' : 'Baixar PDF';
            submit.className = excel ? 'btn btn-excel' : 'btn btn-pdf';
            modal.showModal();
        });
    });

    document.querySelectorAll('[data-close-export]').forEach((button) => {
        button.addEventListener('click', () => modal.close());
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });

    form.addEventListener('submit', () => {
        window.setTimeout(() => modal.close(), 150);
    });
})();
