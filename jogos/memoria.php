<?php
// Carrega autenticação e funções compartilhadas do sistema.
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Define o aluno e a matéria associada à partida individual.
$user = get_logged_user();
$pageTitle = 'Memória Matemática | MathPlay';
$ano = (int) ($user['ano_escolar'] ?? 6);
$materiaId = (int) ($_GET['materia_id'] ?? 1);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="/igor_tcc_teste/assets/css/jogos.css?v=6">
<div class="memory-shell">
    <div class="container">
        <div class="memory-header card-glass">
            <div class="hud-item"><span>Pontos</span><strong id="pontosMemory">0</strong></div>
            <div class="hud-item"><span>XP</span><strong id="xpMemory">0</strong></div>
            <div class="hud-item"><span>Movimentos</span><strong id="movimentos">0</strong></div>
            <div class="hud-item"><span>Cronômetro</span><strong id="tempoMemory">300</strong>s</div>
            <div class="hud-item"><span>Cartas</span><strong id="cartasEncontradas">0</strong></div>
            <div class="hud-item"><span>Total</span><strong id="totalPares">0</strong></div>
            <a href="/igor_tcc_teste/jogos/index.php" class="btn btn-outline-primary memory-exit-button">
                <i class="bi bi-box-arrow-left"></i> Sair do jogo
            </a>
        </div>

        <div class="memory-board" id="memoryBoard"></div>
    </div>
</div>

<div class="memory-result-overlay" id="memoryResult" hidden>
    <section class="memory-result-modal" role="dialog" aria-modal="true" aria-labelledby="memoryResultTitle">
        <div class="memory-result-icon"><i class="bi bi-trophy-fill"></i></div>
        <span class="section-badge" id="memoryResultBadge">Partida concluída</span>
        <h2 id="memoryResultTitle">Parabéns!</h2>
        <p id="memoryResultMessage">Você concluiu o desafio.</p>
        <div class="memory-result-stats" id="memoryResultStats"></div>
        <button type="button" class="btn btn-primary" id="memoryResultButton"><i class="bi bi-arrow-clockwise"></i> Jogar novamente</button>
    </section>
</div>

<script>
    // Identifica o aluno e a matéria no registro final da partida.
    const userId = <?php echo (int) $user['id']; ?>;
    const anoEscolar = <?php echo (int) $user['ano_escolar']; ?>;
    const materiaId = <?php echo $materiaId > 0 ? $materiaId : 1; ?>;
    const nomeUsuario = <?php echo json_encode($user['nome'], JSON_UNESCAPED_UNICODE); ?>;
    const cartasBase = [
        { id: 1, texto: '6 × 8', valor: '48' },
        { id: 2, texto: '1/2', valor: '50%' },
        { id: 3, texto: 'x + 3 = 7', valor: '4' },
        { id: 4, texto: '2²', valor: '4' },
        { id: 5, texto: '√9', valor: '3' },
        { id: 6, texto: '25%', valor: '1/4' },
        { id: 7, texto: '3 × 5', valor: '15' },
        { id: 8, texto: '8 - 2', valor: '6' }
    ];

    // Estado do tabuleiro, pontuação e cronômetro de cinco minutos.
    let deck = [];
    let primeiro = null;
    let segundo = null;
    let travado = false;
    let pontos = 0;
    let xp = 0;
    let movimentos = 0;
    let tempo = 300;
    let timer = null;
    let cartasEncontradas = 0;
    let partidaFinalizada = false;

    // Embaralha as cartas sem alterar o conjunto original.
    function embaralhar(array) {
        return [...array].sort(() => Math.random() - 0.5);
    }

    // Cria os pares, desenha o tabuleiro e inicia o tempo.
    function criarDeck() {
        const pares = embaralhar([...cartasBase, ...cartasBase]).map((item, index) => ({ ...item, uniqueId: index }));
        deck = pares;
        renderizarCartas();
        atualizarHud();
        iniciarCronometro();
    }

    // Constrói visualmente todas as cartas do jogo.
    function renderizarCartas() {
        const board = document.getElementById('memoryBoard');
        board.innerHTML = deck.map((carta) => `
            <button class="memory-card" data-id="${carta.uniqueId}" data-valor="${carta.valor}">
                <span class="card-front">?</span>
                <span class="card-back">${carta.texto}</span>
            </button>
        `).join('');

        document.querySelectorAll('.memory-card').forEach(card => {
            card.addEventListener('click', () => flipCard(card));
        });
    }

    // Revela cartas, compara pares e soma pontos quando há acerto.
    function flipCard(card) {
        if (travado || card.classList.contains('matched') || card.classList.contains('revealed')) return;

        card.classList.add('revealed');
        const valor = card.dataset.valor;
        if (!primeiro) {
            primeiro = { card, valor };
            return;
        }

        segundo = { card, valor };
        movimentos += 1;
        atualizarHud();

        if (primeiro.valor === segundo.valor) {
            setTimeout(() => {
                primeiro.card.classList.add('matched');
                segundo.card.classList.add('matched');
                cartasEncontradas += 1;
                pontos += 100;
                xp += 20;
                atualizarHud();
                resetTurno();
                if (cartasEncontradas >= cartasBase.length) {
                    finalizarJogo();
                }
            }, 500);
        } else {
            travado = true;
            setTimeout(() => {
                primeiro.card.classList.remove('revealed');
                segundo.card.classList.remove('revealed');
                resetTurno();
            }, 700);
        }
    }

    function resetTurno() {
        primeiro = null;
        segundo = null;
        travado = false;
    }

    // Mantém os indicadores do jogo sincronizados com o estado atual.
    function atualizarHud() {
        document.getElementById('pontosMemory').textContent = pontos;
        document.getElementById('xpMemory').textContent = xp;
        document.getElementById('movimentos').textContent = movimentos;
        document.getElementById('tempoMemory').textContent = tempo;
        document.getElementById('cartasEncontradas').textContent = cartasEncontradas;
        document.getElementById('totalPares').textContent = cartasBase.length;
    }

    // Desconta um segundo por vez até completar cinco minutos.
    function iniciarCronometro() {
        clearInterval(timer);
        timer = setInterval(() => {
            tempo -= 1;
            atualizarHud();
            if (tempo <= 0) {
                clearInterval(timer);
                finalizarJogo('Tempo esgotado', 'O tempo acabou. Tente novamente para melhorar sua pontuação.', 'Tentar novamente');
            }
        }, 1000);
    }

    // Registra a partida individual e abre o modal de resultado.
    function finalizarJogo(titulo = 'Parabéns!', mensagem = 'Você concluiu todos os pares da Memória Matemática.', textoBotao = 'Jogar novamente') {
        if (partidaFinalizada) return;
        partidaFinalizada = true;
        clearInterval(timer);
        fetch('/igor_tcc_teste/api/registrar_resposta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario_id: userId,
                finalizar: true,
                nova_partida: true,
                jogo: 'memoria',
                materia_id: materiaId,
                dificuldade: 'Médio',
                pontuacao: pontos,
                xp_ganho: xp,
                acertos: cartasEncontradas,
                erros: Math.max(movimentos - cartasEncontradas, 0),
                total_questoes: cartasBase.length,
                tempo: 300 - tempo,
                ano_escolar: anoEscolar,
                equipe_nomes: { tipo: 'individual', nomes: [nomeUsuario] }
            })
        }).catch(() => {});
        mostrarResultado(titulo, mensagem, [
            ['Pontuação', pontos],
            ['XP conquistado', xp],
            ['Movimentos', movimentos],
            ['Tempo usado', `${300 - tempo}s`]
        ], textoBotao);
    }

    function mostrarResultado(titulo, mensagem, estatisticas, textoBotao) {
        document.getElementById('memoryResultTitle').textContent = titulo;
        document.getElementById('memoryResultMessage').textContent = mensagem;
        document.getElementById('memoryResultStats').innerHTML = estatisticas.map(([rotulo, valor]) => `
            <div><span>${rotulo}</span><strong>${valor}</strong></div>
        `).join('');
        document.getElementById('memoryResultButton').innerHTML = `<i class="bi bi-arrow-clockwise"></i> ${textoBotao}`;
        document.getElementById('memoryResultButton').onclick = () => window.location.reload();
        document.getElementById('memoryResult').hidden = false;
    }

    criarDeck();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
