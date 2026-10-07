// Controla preferências visuais e recursos de acessibilidade compartilhados pelas páginas.
document.addEventListener('DOMContentLoaded', () => {
    // Preenche uma largura padrão apenas quando a barra de progresso ainda não tem valor.
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = progressBar.style.width || '50%';
    }

    const themeToggle = document.getElementById('themeToggle');
    const themeStatus = document.getElementById('themeStatus');
    if (themeToggle && themeStatus) {
        // Centraliza as mudanças visuais e sincroniza também as cores dos gráficos existentes.
        const applyTheme = theme => {
            document.documentElement.dataset.theme = theme;
            const isDark = theme === 'dark';
            const chartColor = isDark ? '#cbd5e1' : '#666';
            const chartGridColor = isDark ? '#344155' : 'rgba(0, 0, 0, 0.1)';
            themeToggle.setAttribute('aria-pressed', String(isDark));
            themeToggle.setAttribute('aria-label', `Ativar tema ${isDark ? 'claro' : 'escuro'}`);
            themeToggle.innerHTML = `<i class="bi ${isDark ? 'bi-sun-fill' : 'bi-moon-stars-fill'}" aria-hidden="true"></i><span>Tema ${isDark ? 'claro' : 'escuro'}</span>`;

            if (window.Chart) {
                window.Chart.defaults.color = chartColor;
                window.Chart.defaults.borderColor = chartGridColor;
                Object.values(window.Chart.instances).forEach(chart => {
                    Object.values(chart.options.scales || {}).forEach(scale => {
                        scale.ticks.color = chartColor;
                        scale.grid.color = chartGridColor;
                    });
                    chart.update('none');
                });
            }
        };

        // Aplica o tema já escolhido pela página e alterna entre claro e escuro ao clicar.
        applyTheme(document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');
        themeToggle.addEventListener('click', () => {
            const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            applyTheme(theme);
            try {
                window.localStorage.setItem('mathplay-theme', theme);
                themeStatus.textContent = `Tema ${theme === 'dark' ? 'escuro' : 'claro'} ativado.`;
            } catch (error) {
                themeStatus.textContent = `Tema ${theme === 'dark' ? 'escuro' : 'claro'} ativado nesta página, mas não foi possível salvar a preferência no navegador.`;
                console.warn('Não foi possível salvar a preferência de tema.', error);
            }
        });
    }

    const toggle = document.getElementById('accessibilityToggle');
    const panel = document.getElementById('accessibilityPanel');
    const status = document.getElementById('accessibilityStatus');
    if (!toggle || !panel || !status) return;

    const storageKey = 'mathplay-font-scale';
    const minimumScale = 100;
    const maximumScale = 150;
    const scaleStep = 10;
    let fontScale = minimumScale;

    // Só aceita preferências inteiras dentro da faixa e dos incrementos permitidos.
    try {
        const savedScale = Number(window.localStorage.getItem(storageKey));
        if (Number.isInteger(savedScale) && savedScale >= minimumScale && savedScale <= maximumScale && savedScale % scaleStep === 0) {
            fontScale = savedScale;
        }
    } catch (error) {
        console.warn('Não foi possível carregar a preferência de acessibilidade salva.', error);
    }

    const applyFontScale = () => {
        document.documentElement.style.setProperty('--accessibility-font-scale', `${fontScale}%`);
    };

    const saveFontScale = () => {
        applyFontScale();
        try {
            window.localStorage.setItem(storageKey, String(fontScale));
            status.textContent = `Tamanho do texto: ${fontScale}%.`;
        } catch (error) {
            status.textContent = 'O tamanho foi ajustado nesta página, mas não foi possível salvar a preferência no navegador.';
            console.warn('Não foi possível salvar a preferência de acessibilidade.', error);
        }
    };

    applyFontScale();

    toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        panel.hidden = expanded;
        if (!expanded) {
            // Leva o foco ao primeiro controle para que o painel também seja fácil de usar pelo teclado.
            panel.querySelector('button')?.focus();
        }
    });

    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            // Escape fecha o painel e devolve o foco ao botão que o abriu.
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });

    document.getElementById('increaseText').addEventListener('click', () => {
        fontScale = Math.min(fontScale + scaleStep, maximumScale);
        saveFontScale();
    });

    document.getElementById('decreaseText').addEventListener('click', () => {
        fontScale = Math.max(fontScale - scaleStep, minimumScale);
        saveFontScale();
    });

    document.getElementById('resetText').addEventListener('click', () => {
        fontScale = minimumScale;
        saveFontScale();
    });
});
