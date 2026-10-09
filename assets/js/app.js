// Controla preferências visuais e recursos de acessibilidade compartilhados pelas páginas.
window.mostrarCelebracaoFase = fase => {
    if (!fase || document.querySelector('.phase-up-overlay')) return;

    const focoAnterior = document.activeElement;
    const sobreposicao = document.createElement('div');
    sobreposicao.className = 'phase-up-overlay';
    sobreposicao.setAttribute('role', 'dialog');
    sobreposicao.setAttribute('aria-modal', 'true');
    sobreposicao.setAttribute('aria-labelledby', 'phaseUpTitle');
    sobreposicao.setAttribute('aria-describedby', 'phaseUpMessage');
    sobreposicao.innerHTML = `
        <section class="phase-up-card">
            <div class="phase-up-icon"><i class="bi bi-stars" aria-hidden="true"></i></div>
            <span class="section-badge">Nova conquista</span>
            <h2 id="phaseUpTitle">Você avançou de fase!</h2>
            <p class="phase-up-name"></p>
            <p id="phaseUpMessage">Cada desafio vencido fortalece o que você aprendeu. Continue praticando e descubra até onde pode chegar!</p>
            <button type="button" class="btn btn-primary" data-phase-dismiss>Continuar</button>
        </section>
    `;
    sobreposicao.querySelector('.phase-up-name').textContent = `Fase ${fase.numero}: ${fase.nome}`;

    const fechar = () => {
        sobreposicao.remove();
        document.removeEventListener('keydown', aoPressionarEscape);
        focoAnterior?.focus?.();
    };
    const aoPressionarEscape = event => {
        if (event.key === 'Escape') fechar();
    };

    sobreposicao.querySelector('[data-phase-dismiss]').addEventListener('click', fechar);
    document.addEventListener('keydown', aoPressionarEscape);
    document.body.append(sobreposicao);
    sobreposicao.querySelector('[data-phase-dismiss]').focus();
};

document.addEventListener('DOMContentLoaded', () => {
    // Preenche uma largura padrão apenas quando a barra de progresso ainda não tem valor.
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = progressBar.style.width || '50%';
    }

    document.querySelectorAll('input[type="password"]').forEach((passwordInput, index) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'password-field';
        passwordInput.parentNode.insertBefore(wrapper, passwordInput);
        wrapper.append(passwordInput);
        passwordInput.classList.add('password-input');

        if (!passwordInput.id) {
            passwordInput.id = `password-field-${index + 1}`;
        }

        const visibilityButton = document.createElement('button');
        visibilityButton.type = 'button';
        visibilityButton.className = 'password-toggle';
        visibilityButton.setAttribute('aria-label', 'Mostrar senha');
        visibilityButton.setAttribute('aria-controls', passwordInput.id);
        visibilityButton.setAttribute('aria-pressed', 'false');
        visibilityButton.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
        visibilityButton.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'text';
            passwordInput.type = isVisible ? 'password' : 'text';
            visibilityButton.setAttribute('aria-label', isVisible ? 'Mostrar senha' : 'Ocultar senha');
            visibilityButton.setAttribute('aria-pressed', String(!isVisible));
            visibilityButton.innerHTML = `<i class="bi ${isVisible ? 'bi-eye' : 'bi-eye-slash'}" aria-hidden="true"></i>`;
        });
        wrapper.append(visibilityButton);
    });

    const notificationCount = document.getElementById('chatNotificationCount');
    const notificationList = document.getElementById('chatNotificationList');
    const notificationEmpty = document.getElementById('chatNotificationEmpty');
    if (notificationCount && notificationList && notificationEmpty) {
        const atualizarNotificacoes = async () => {
            try {
                const response = await fetch('/igor_tcc_teste/api/chat.php?action=notificacoes', {
                    cache: 'no-store',
                    credentials: 'same-origin'
                });
                if (!response.ok) return;
                const data = await response.json();
                notificationCount.textContent = data.total > 99 ? '99+' : String(data.total);
                notificationCount.hidden = data.total === 0;
                notificationEmpty.hidden = data.notificacoes.length > 0;
                notificationList.querySelectorAll('[data-chat-notification]').forEach(item => item.remove());

                data.notificacoes.forEach(item => {
                    const row = document.createElement('li');
                    row.dataset.chatNotification = 'true';
                    const link = document.createElement('a');
                    link.className = 'dropdown-item chat-notification-item';
                    link.href = `/igor_tcc_teste/public/chat.php?conversa_id=${encodeURIComponent(item.conversa_id)}`;
                    const sender = document.createElement('strong');
                    sender.textContent = item.remetente_nome;
                    const preview = document.createElement('span');
                    preview.textContent = item.conteudo;
                    link.append(sender, preview);
                    row.append(link);
                    notificationEmpty.after(row);
                });
            } catch (error) {
                console.warn('Não foi possível atualizar as notificações do chat.', error);
            }
        };

        atualizarNotificacoes();
        window.setInterval(atualizarNotificacoes, 10000);
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
