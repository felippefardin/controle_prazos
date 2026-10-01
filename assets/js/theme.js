(function () {
    'use strict';

    var storageKey = 'controle-prazos-theme';
    var root = document.documentElement;

    function preferredTheme() {
        try {
            var saved = localStorage.getItem(storageKey);
            if (saved === 'light' || saved === 'dark') return saved;
        } catch (error) {
            // O tema continua funcionando mesmo se o armazenamento estiver indisponível.
        }

        return window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches
            ? 'light'
            : 'dark';
    }

    function applyTheme(theme) {
        root.dataset.theme = theme;
        root.style.colorScheme = theme;

        var button = document.getElementById('theme-toggle');
        if (!button) return;

        var isDark = theme === 'dark';
        button.setAttribute('aria-pressed', String(isDark));
        button.setAttribute('aria-label', isDark ? 'Ativar modo claro' : 'Ativar modo escuro');
        button.setAttribute('title', isDark ? 'Ativar modo claro' : 'Ativar modo escuro');
        button.querySelector('.theme-toggle-icon').textContent = isDark ? '☀' : '☾';
        button.querySelector('.theme-toggle-text').textContent = isDark ? 'Claro' : 'Escuro';
    }

    applyTheme(preferredTheme());

    document.addEventListener('DOMContentLoaded', function () {
        var button = document.createElement('button');
        button.id = 'theme-toggle';
        button.className = 'theme-toggle';
        button.type = 'button';
        button.innerHTML = '<span class="theme-toggle-icon" aria-hidden="true"></span><span class="theme-toggle-text"></span>';
        button.addEventListener('click', function () {
            var nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem(storageKey, nextTheme); } catch (error) {}
            applyTheme(nextTheme);
        });
        var headerArea = document.querySelector('.user-area');
        (headerArea || document.body).appendChild(button);
        applyTheme(root.dataset.theme || preferredTheme());
    });
}());
