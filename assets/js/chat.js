document.addEventListener('DOMContentLoaded', () => {
    const configElement = document.getElementById('chatConfig');
    if (!configElement) return;

    const config = JSON.parse(configElement.textContent);
    const apiUrl = '/igor_tcc_teste/api/chat.php';
    const conversationList = document.getElementById('chatConversationList');
    const searchInput = document.getElementById('chatContactSearch');
    const searchResults = document.getElementById('chatContactResults');
    const emptyState = document.getElementById('chatEmptyState');
    const activeWindow = document.getElementById('chatActiveWindow');
    const messagesContainer = document.getElementById('chatMessages');
    const messageForm = document.getElementById('chatMessageForm');
    const messageInput = document.getElementById('chatMessageInput');
    const statusElement = document.getElementById('chatStatus');
    let conversaAtual = 0;
    let buscaTimer = null;
    let pollingMensagens = false;
    let ultimaMensagemRenderizada = '';

    async function request(url, options = {}) {
        const response = await fetch(url, {
            cache: 'no-store',
            credentials: 'same-origin',
            ...options,
            headers: {
                ...(options.body ? { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': config.csrfToken } : {}),
                ...(options.headers || {})
            }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Não foi possível concluir esta ação.');
        }
        return data;
    }

    function rotuloTipo(tipo) {
        return tipo === 'professor' ? 'Professor(a)' : 'Aluno(a)';
    }

    function mostrarStatus(texto, erro = false) {
        statusElement.textContent = texto;
        statusElement.classList.toggle('is-error', erro);
    }

    function criarBotaoContato(contato, origemBusca = false) {
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = origemBusca ? 'chat-search-result' : 'chat-conversation-item';
        botao.setAttribute('role', 'option');
        botao.dataset.contatoId = String(contato.contato_id || contato.id);

        const avatar = document.createElement('span');
        avatar.className = 'chat-list-avatar';
        avatar.textContent = (contato.nome || '?').trim().charAt(0).toUpperCase();

        const detalhes = document.createElement('span');
        detalhes.className = 'chat-list-details';
        const nome = document.createElement('strong');
        nome.textContent = contato.nome;
        const subtitulo = document.createElement('span');
        subtitulo.textContent = origemBusca
            ? `${rotuloTipo(contato.tipo)}${contato.tipo === 'aluno' && contato.ano_escolar ? ` · ${contato.ano_escolar}º ano` : ''}`
            : (contato.ultima_mensagem || rotuloTipo(contato.tipo));
        detalhes.append(nome, subtitulo);
        botao.append(avatar, detalhes);

        if (!origemBusca && Number(contato.nao_lidas) > 0) {
            const naoLidas = document.createElement('span');
            naoLidas.className = 'chat-unread-count';
            naoLidas.textContent = String(contato.nao_lidas);
            naoLidas.setAttribute('aria-label', `${contato.nao_lidas} mensagens não lidas`);
            botao.append(naoLidas);
        }

        if (!origemBusca && Number(contato.conversa_id) === conversaAtual) {
            botao.classList.add('is-selected');
            botao.setAttribute('aria-current', 'true');
        }

        botao.addEventListener('click', () => abrirConversa(Number(contato.contato_id || contato.id)));
        return botao;
    }

    async function atualizarConversas() {
        try {
            const data = await request(`${apiUrl}?action=conversas`);
            conversationList.replaceChildren();
            if (!data.conversas.length) {
                const vazio = document.createElement('p');
                vazio.className = 'chat-list-empty';
                vazio.textContent = 'Nenhuma conversa ainda.';
                conversationList.append(vazio);
                return;
            }
            data.conversas.forEach(conversa => conversationList.append(criarBotaoContato(conversa)));
        } catch (error) {
            mostrarStatus(error.message, true);
        }
    }

    async function buscarContatos() {
        const busca = searchInput.value.trim();
        if (busca.length < 2) {
            searchResults.hidden = true;
            searchResults.replaceChildren();
            return;
        }

        try {
            const data = await request(`${apiUrl}?action=contatos&q=${encodeURIComponent(busca)}`);
            searchResults.replaceChildren();
            if (!data.contatos.length) {
                const vazio = document.createElement('p');
                vazio.className = 'chat-list-empty';
                vazio.textContent = 'Nenhum contato encontrado.';
                searchResults.append(vazio);
            } else {
                data.contatos.forEach(contato => searchResults.append(criarBotaoContato(contato, true)));
            }
            searchResults.hidden = false;
        } catch (error) {
            mostrarStatus(error.message, true);
        }
    }

    async function abrirConversa(contatoId) {
        mostrarStatus('');
        try {
            const data = await request(apiUrl, {
                method: 'POST',
                body: new URLSearchParams({ action: 'abrir', contato_id: String(contatoId) })
            });
            const conversa = (await request(`${apiUrl}?action=conversas`)).conversas.find(item => Number(item.conversa_id) === Number(data.conversa_id));
            if (!conversa) throw new Error('Não foi possível carregar os dados do contato.');

            conversaAtual = Number(data.conversa_id);
            document.getElementById('chatContactName').textContent = conversa.nome;
            document.getElementById('chatContactRole').textContent = rotuloTipo(conversa.tipo);
            document.getElementById('chatContactInitials').textContent = conversa.nome.trim().charAt(0).toUpperCase();
            emptyState.hidden = true;
            activeWindow.hidden = false;
            searchResults.hidden = true;
            searchInput.value = '';
            messageInput.focus();
            await Promise.all([carregarMensagens(true), atualizarConversas()]);
            const url = new URL(window.location.href);
            url.searchParams.set('conversa_id', String(conversaAtual));
            window.history.replaceState({}, '', url);
        } catch (error) {
            mostrarStatus(error.message, true);
        }
    }

    function formatarHorario(valor) {
        const data = new Date(String(valor).replace(' ', 'T'));
        if (Number.isNaN(data.getTime())) return '';
        return new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(data);
    }

    async function carregarMensagens(forcarRolagem = false) {
        if (!conversaAtual || pollingMensagens || document.hidden) return;
        pollingMensagens = true;
        const estavaNoFim = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < 60;
        try {
            const data = await request(`${apiUrl}?action=mensagens&conversa_id=${conversaAtual}`);
            const novaChave = data.mensagens.map(item => item.id).join(',');
            if (novaChave !== ultimaMensagemRenderizada) {
                messagesContainer.replaceChildren();
                data.mensagens.forEach(mensagem => {
                    const linha = document.createElement('div');
                    linha.className = `chat-message-row${Number(mensagem.remetente_id) === config.usuarioId ? ' is-mine' : ''}`;
                    const bolha = document.createElement('article');
                    bolha.className = 'chat-message-bubble';
                    const texto = document.createElement('p');
                    texto.textContent = mensagem.conteudo;
                    const horario = document.createElement('time');
                    horario.dateTime = mensagem.enviada_em;
                    horario.textContent = formatarHorario(mensagem.enviada_em);
                    bolha.append(texto, horario);
                    linha.append(bolha);
                    messagesContainer.append(linha);
                });
                ultimaMensagemRenderizada = novaChave;
                if (forcarRolagem || estavaNoFim) messagesContainer.scrollTop = messagesContainer.scrollHeight;
                atualizarConversas();
            }
            mostrarStatus('');
        } catch (error) {
            mostrarStatus(error.message, true);
        } finally {
            pollingMensagens = false;
        }
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(buscaTimer);
        buscaTimer = setTimeout(buscarContatos, 250);
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('.chat-sidebar')) searchResults.hidden = true;
    });

    messageForm.addEventListener('submit', async event => {
        event.preventDefault();
        const conteudo = messageInput.value.trim();
        if (!conversaAtual || !conteudo) return;

        const botaoEnviar = messageForm.querySelector('button[type="submit"]');
        botaoEnviar.disabled = true;
        try {
            await request(apiUrl, {
                method: 'POST',
                body: new URLSearchParams({ action: 'enviar', conversa_id: String(conversaAtual), conteudo })
            });
            messageInput.value = '';
            await Promise.all([carregarMensagens(true), atualizarConversas()]);
            messageInput.focus();
        } catch (error) {
            mostrarStatus(error.message, true);
        } finally {
            botaoEnviar.disabled = false;
        }
    });

    atualizarConversas();
    const conversaSolicitada = Number(new URLSearchParams(window.location.search).get('conversa_id') || 0);
    if (conversaSolicitada > 0) {
        request(`${apiUrl}?action=conversas`).then(data => {
            const conversa = data.conversas.find(item => Number(item.conversa_id) === conversaSolicitada);
            if (conversa) abrirConversa(Number(conversa.contato_id));
        }).catch(error => mostrarStatus(error.message, true));
    }

    window.setInterval(() => {
        atualizarConversas();
        carregarMensagens();
    }, 5000);
});