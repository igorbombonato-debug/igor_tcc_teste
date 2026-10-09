<?php
// Tela do jogo individual de memória; os pares e as regras são controlados no JavaScript.
// Carrega autenticação e funções compartilhadas do sistema.
require_once __DIR__ . '/../includes/auth.php';
require_student();

// Define o aluno e a matéria associada à partida individual.
$user = get_logged_user();
$pageTitle = 'Memória Matemática | Mathematics Education';
$ano = (int) ($user['ano_escolar'] ?? 6);
$materias = obter_materias($ano);
$materiaIds = array_map('intval', array_column($materias, 'id'));
$materiaSolicitada = (int) ($_GET['materia_id'] ?? 0);
$materiaId = in_array($materiaSolicitada, $materiaIds, true)
    ? $materiaSolicitada
    : (int) ($materiaIds[0] ?? 0);

$questoes = obter_questoes_por_ano($ano, null, $materiaId ?: null);
if (empty($questoes)) {
    $questoes = obter_questoes_por_ano($ano);
}
$questoes = array_slice($questoes, 0, 8);

$cartasBase = [];
foreach ($questoes as $questao) {
    $respostaCorreta = $questao['resposta_correta'] ?? 'A';
    $textoResposta = '';

    switch (strtoupper($respostaCorreta)) {
        case 'A':
            $textoResposta = $questao['alternativa_a'] ?? '';
            break;
        case 'B':
            $textoResposta = $questao['alternativa_b'] ?? '';
            break;
        case 'C':
            $textoResposta = $questao['alternativa_c'] ?? '';
            break;
        case 'D':
            $textoResposta = $questao['alternativa_d'] ?? '';
            break;
        default:
            $textoResposta = $questao['alternativa_a'] ?? '';
            break;
    }

    if ($questao['enunciado'] && $textoResposta) {
        $cartasBase[] = [
            'id' => (int) $questao['id'],
            'pergunta' => $questao['enunciado'],
            'resposta' => $textoResposta,
        ];
    }
}

if (count($cartasBase) < 2) {
    $cartasBase = [
        ['id' => 1, 'pergunta' => 'Qual é o valor de 6 × 8?', 'resposta' => '48'],
        ['id' => 2, 'pergunta' => 'Quanto vale 1/2 em porcentagem?', 'resposta' => '50%'],
        ['id' => 3, 'pergunta' => 'Se x + 3 = 7, qual é o valor de x?', 'resposta' => '4'],
        ['id' => 4, 'pergunta' => 'Qual é o valor de 2²?', 'resposta' => '4'],
    ];
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="/igor_tcc_teste/assets/css/jogos.css?v=8">
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
        <div class="memory-result-actions">
            <button type="button" class="btn btn-primary" id="memoryResultButton"><i class="bi bi-arrow-clockwise"></i> Jogar novamente</button>
            <a href="/igor_tcc_teste/jogos/index.php" class="btn btn-outline-primary"><i class="bi bi-house"></i> Voltar ao menu</a>
        </div>
    </section>
</div>

<script>
    // Identifica o aluno e a matéria no registro final da partida.
    const anoEscolar = <?php echo (int) $user['ano_escolar']; ?>;
    const materiaId = <?php echo $materiaId; ?>;
    const nomeUsuario = <?php echo json_encode($user['nome'], JSON_UNESCAPED_UNICODE); ?>;
    const cartasBase = <?php echo json_encode($cartasBase, JSON_UNESCAPED_UNICODE); ?>;

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
    const tentativas = [];

    // Embaralha as cartas sem alterar o conjunto original.
    function embaralhar(array) {
        return [...array].sort(() => Math.random() - 0.5);
    }

    // Cria os pares pergunta x resposta, desenha o tabuleiro e inicia o tempo.
    function criarDeck() {
        const pares = [];

        cartasBase.forEach((item) => {
            pares.push({
                id: `${item.id}-pergunta`,
                pairId: item.id,
                tipo: 'pergunta',
                texto: item.pergunta,
                matchKey: item.id
            });
            pares.push({
                id: `${item.id}-resposta`,
                pairId: item.id,
                tipo: 'resposta',
                texto: item.resposta,
                matchKey: item.id
            });
        });

        deck = embaralhar(pares);
        renderizarCartas();
        atualizarHud();
        iniciarCronometro();
    }

    // Constrói visualmente todas as cartas do jogo.
    function renderizarCartas() {
        const board = document.getElementById('memoryBoard');
        board.innerHTML = deck.map((carta) => `
            <button class="memory-card" data-id="${carta.id}" data-match-key="${carta.matchKey}" data-tipo="${carta.tipo}">
                <span class="card-front">?</span>
                <span class="card-back">${carta.texto}</span>
            </button>
        `).join('');

        document.querySelectorAll('.memory-card').forEach(card => {
            card.addEventListener('click', () => flipCard(card));
        });
    }

    // Revela cartas, compara pergunta com resposta e soma pontos quando há acerto.
    function flipCard(card) {
        if (travado || card.classList.contains('matched') || card.classList.contains('revealed')) return;

        card.classList.add('revealed');
        const matchKey = Number(card.dataset.matchKey);
        const tipo = card.dataset.tipo;
        if (!primeiro) {
            primeiro = { card, matchKey, tipo };
            return;
        }

        segundo = { card, matchKey, tipo };
        movimentos += 1;
        atualizarHud();

        const textoPrimeiraCarta = primeiro.card.querySelector('.card-back').textContent;
        const textoSegundaCarta = segundo.card.querySelector('.card-back').textContent;
        const acertouPar = primeiro.matchKey === segundo.matchKey && primeiro.tipo !== segundo.tipo;
        const itemCarta = cartasBase.find((item) => Number(item.id) === matchKey) || { pergunta: 'Pergunta', resposta: 'Resposta' };
        tentativas.push({
            enunciado: itemCarta.pergunta || textoPrimeiraCarta,
            resposta_usuario: textoSegundaCarta,
            resposta_correta: itemCarta.resposta || textoPrimeiraCarta,
            correta: acertouPar
        });

        if (acertouPar) {
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
        // A tela de resultado aparece imediatamente; o envio ao servidor termina em paralelo.
        const registro = fetch('/igor_tcc_teste/api/registrar_resposta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
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
                equipe_nomes: { tipo: 'individual', nomes: [nomeUsuario] },
                respostas: tentativas
            })
        }).then(async response => {
            if (!response.ok) throw new Error('Falha ao salvar a partida.');
            const resultado = await response.json();
            if (!resultado.success) throw new Error(resultado.message || 'Falha ao salvar a partida.');
        });
        mostrarResultado(titulo, mensagem, [
            ['Pontuação', pontos],
            ['XP conquistado', xp],
            ['Movimentos', movimentos],
            ['Tempo usado', `${300 - tempo}s`]
        ], textoBotao);
        registro.catch(() => {
            document.getElementById('memoryResultMessage').textContent =
                `${mensagem} Não foi possível salvar esta partida; atualize a página e tente novamente.`;
        });
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
