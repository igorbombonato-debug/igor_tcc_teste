<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = get_logged_user();
$pageTitle = 'Queimada Matemática | MathPlay';

$ano = (int) ($user['ano_escolar'] ?? 6);
$dificuldade = $_GET['dificuldade'] ?? 'Médio';
$materiaId = (int) ($_GET['materia_id'] ?? 0);

$questoes = obter_questoes_por_ano($ano, $dificuldade, $materiaId ?: null);
if (empty($questoes)) {
    $questoes = obter_questoes_por_ano($ano);
}

$questaoAtual = $questoes[0] ?? null;
if ($questaoAtual) {
    $alternativas = [
        'A' => $questaoAtual['alternativa_a'],
        'B' => $questaoAtual['alternativa_b'],
        'C' => $questaoAtual['alternativa_c'],
        'D' => $questaoAtual['alternativa_d'],
    ];
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="/igor_tcc_teste/assets/css/jogos.css?v=2">
<div class="game-shell">
        <section class="team-setup card-glass" id="teamSetup">
            <div class="setup-copy">
                <span class="section-badge">Antes de jogar</span>
                <h1>Monte sua equipe</h1>
                <p>Forme dois times aleatoriamente antes de começar.</p>
            </div>
            <div class="setup-players" id="setupPlayers"></div>
            <div class="setup-actions">
                <button class="btn btn-outline-primary" id="sortearTimes" type="button"><i class="bi bi-shuffle"></i> Sortear times</button>
                <button class="btn btn-primary" id="iniciarPartida" type="button"><i class="bi bi-play-fill"></i> Começar partida</button>
            </div>
        </section>
        <div class="game-header card-glass">
            <div class="hud-item"><span>Pontos</span><strong id="pontos">0</strong></div>
            <div class="hud-item"><span>XP</span><strong id="xp">0</strong></div>
            <div class="hud-item"><span>Vidas</span><strong id="vidas">3</strong></div>
            <div class="hud-item"><span>Questão</span><strong id="questaoAtual">1</strong></div>
            <div class="hud-item"><span>Cronômetro</span><strong id="tempo">20</strong>s</div>
            <div class="hud-item"><span>Progresso</span><strong id="progresso">0%</strong></div>
        </div>

        <div class="game-area card-glass">
            <div class="question-box">
                <div id="feedback" class="feedback-box hidden">ACERTOU!</div>
                <h2 id="enunciado"><?php echo e($questaoAtual['enunciado'] ?? 'Carregando pergunta...'); ?></h2>
            </div>

            <div class="turn-indicator" id="turnIndicator">Prepare os times para começar.</div>
            <div class="quadra" aria-label="Quadra da queimada matemática">
                <section class="time-panel time-azul">
                    <div class="time-heading"><span class="team-dot"></span><div><strong>Time Azul</strong><small>Jogador da vez destacado</small></div></div>
                    <div class="jogadores" id="timeAzul"></div>
                </section>
                <div class="quadra-divider"><span>VS</span></div>
                <section class="time-panel time-vermelho">
                    <div class="time-heading"><span class="team-dot"></span><div><strong>Time Vermelho</strong><small>Jogador da vez destacado</small></div></div>
                    <div class="jogadores" id="timeVermelho"></div>
                </section>
            </div>
        </div>

        <div id="gameOver" class="game-over hidden">
            <h2>GAME OVER</h2>
            <p>Pontuação: <strong id="finalPontos">0</strong></p>
            <p>Acertos: <strong id="finalAcertos">0</strong></p>
            <p>Erros: <strong id="finalErros">0</strong></p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/igor_tcc_teste/jogos/queimada.php" class="btn btn-primary">JOGAR NOVAMENTE</a>
                <a href="/igor_tcc_teste/public/dashboard.php" class="btn btn-outline-primary">VOLTAR AO MENU</a>
            </div>
        </div>
    </div>
</div>

<script>
    const questoes = <?php echo json_encode($questoes, JSON_UNESCAPED_UNICODE); ?>;
    const userId = <?php echo (int) $user['id']; ?>;
    const anoEscolar = <?php echo (int) $user['ano_escolar']; ?>;
    const materiaId = <?php echo (int) ($materiaId ?: 0); ?>;
    const dificuldade = <?php echo json_encode($dificuldade); ?>;
    let indice = 0;
    let pontos = 0;
    let xp = 0;
    let vidas = 3;
    let acertos = 0;
    let erros = 0;
    let tempo = 20;
    let timer = null;
    let combo = 0;
    let turnLocked = false;
    const jogadoresEliminados = new Set();
    let times = { azul: [], vermelho: [] };
    let jogadorDaVez = null;
    const nomesJogadores = ['Luna', 'Davi', 'Bia', 'Ravi', 'Noah', 'Lia', 'Theo', 'Maya'];

    function embaralhar(lista) {
        return [...lista].sort(() => Math.random() - 0.5);
    }

    function montarTimes() {
        const jogadores = embaralhar(nomesJogadores);
        times = { azul: jogadores.slice(0, 4), vermelho: jogadores.slice(4, 8) };
        document.getElementById('setupPlayers').innerHTML = `
            <div class="setup-team setup-team-blue"><strong>Time Azul</strong><span>${times.azul.join(' • ')}</span></div>
            <div class="setup-team setup-team-red"><strong>Time Vermelho</strong><span>${times.vermelho.join(' • ')}</span></div>`;
        renderizarTimes();
    }

    function renderizarTimes() {
        const respostas = questoes[indice] ? { A: questoes[indice].alternativa_a, B: questoes[indice].alternativa_b, C: questoes[indice].alternativa_c, D: questoes[indice].alternativa_d } : {};
        const letras = ['A', 'B', 'C', 'D'];
        ['azul', 'vermelho'].forEach(time => {
            document.getElementById(`time${time[0].toUpperCase()}${time.slice(1)}`).innerHTML = times[time].map((nome, index) => {
                const id = `${time}-${index}`;
                const letra = letras[index];
                return `<button class="personagem" type="button" data-jogador="${id}" data-resposta="${letra}"><i class="bi bi-person-fill avatar"></i><strong class="nome-jogador">${nome}</strong><span class="resposta">${letra}: ${respostas[letra] || ''}</span></button>`;
            }).join('');
        });
        document.querySelectorAll('.personagem').forEach(personagem => personagem.addEventListener('click', responderComJogador));
    }

    function prepararJogadorDaVez() {
        const vivos = [...document.querySelectorAll('.personagem')].filter(jogador => !jogadoresEliminados.has(jogador.dataset.jogador));
        if (!vivos.length) return;
        const escolhido = vivos[Math.floor(Math.random() * vivos.length)];
        jogadorDaVez = escolhido.dataset.jogador;
        vivos.forEach(jogador => {
            jogador.disabled = jogador !== escolhido;
            jogador.classList.toggle('aguardando', jogador !== escolhido);
            jogador.classList.toggle('vez', jogador === escolhido);
        });
        document.getElementById('turnIndicator').textContent = `Vez de ${escolhido.split('-')[0] === 'azul' ? 'um jogador do Time Azul' : 'um jogador do Time Vermelho'}: escolha o ícone destacado.`;
    }

    function atualizarHud() {
        document.getElementById('pontos').textContent = pontos;
        document.getElementById('xp').textContent = xp;
        document.getElementById('vidas').textContent = vidas;
        document.getElementById('questaoAtual').textContent = Math.min(indice + 1, questoes.length);
        document.getElementById('tempo').textContent = tempo;
        document.getElementById('progresso').textContent = Math.round(((indice) / Math.max(questoes.length, 1)) * 100) + '%';
    }

    function mostrarQuestao() {
        if (indice >= questoes.length) {
            finalizarJogo();
            return;
        }
        const q = questoes[indice];
        document.getElementById('enunciado').textContent = q.enunciado;
        const respostas = { A: q.alternativa_a, B: q.alternativa_b, C: q.alternativa_c, D: q.alternativa_d };
        document.querySelectorAll('.personagem').forEach(jogador => jogador.querySelector('.resposta').textContent = `${jogador.dataset.resposta}: ${respostas[jogador.dataset.resposta]}`);
        prepararJogadorDaVez();
        document.getElementById('feedback').classList.add('hidden');
        document.getElementById('feedback').textContent = 'ACERTOU!';
        tempo = q.dificuldade === 'Difícil' ? 15 : q.dificuldade === 'Médio' ? 18 : 20;
        atualizarHud();
        clearInterval(timer);
        timer = setInterval(() => {
            tempo -= 1;
            if (tempo <= 0) {
                tempo = 0;
                clearInterval(timer);
                registrarResposta({ resposta: null, jogador: null }, false);
            }
            atualizarHud();
        }, 1000);
    }

    function registrarResposta(respostaUsuario, correta) {
        const q = questoes[indice];
        if (!q || turnLocked) return;
        turnLocked = true;

        const jogadorSelecionado = respostaUsuario.jogador
            ? document.querySelector(`.personagem[data-jogador="${CSS.escape(respostaUsuario.jogador)}"]`)
            : null;
        if (correta) {
            combo += 1;
            acertos += 1;
            const base = q.dificuldade === 'Difícil' ? 180 : q.dificuldade === 'Médio' ? 140 : 100;
            const bonus = combo * 50;
            pontos += base + bonus;
            xp += q.dificuldade === 'Difícil' ? 30 : q.dificuldade === 'Médio' ? 20 : 15;
            document.getElementById('feedback').textContent = 'ACERTOU!';
            document.getElementById('feedback').classList.remove('hidden');
            document.getElementById('feedback').classList.add('success');
            jogadorSelecionado?.classList.add('acertou');
        } else {
            combo = 0;
            erros += 1;
            vidas -= 1;
            document.getElementById('feedback').textContent = 'QUASE!';
            document.getElementById('feedback').classList.remove('hidden');
            document.getElementById('feedback').classList.add('error');
            if (jogadorSelecionado) {
                jogadoresEliminados.add(jogadorSelecionado.dataset.jogador);
                jogadorSelecionado.classList.add('eliminado');
                jogadorSelecionado.disabled = true;
            }
        }

        clearInterval(timer);
        indice += 1;
        atualizarHud();

        setTimeout(() => {
            if (vidas <= 0) {
                finalizarJogo();
            } else {
                turnLocked = false;
                mostrarQuestao();
            }
        }, 700);

        fetch('/igor_tcc_teste/api/registrar_resposta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario_id: userId,
                questao_id: q.id,
                resposta_usuario: respostaUsuario.resposta,
                correta: correta ? 1 : 0,
                tempo_resposta: 20 - tempo,
                partida: {
                    jogo: 'queimada',
                    materia_id: q.materia_id,
                    dificuldade: q.dificuldade,
                    pontuacao: pontos,
                    xp_ganho: xp,
                    acertos,
                    erros,
                    total_questoes: questoes.length,
                    tempo: 0,
                    ano_escolar: anoEscolar,
                    materia_nome: 'Geral'
                }
            })
        }).catch(() => {});
    }

    function finalizarJogo() {
        clearInterval(timer);
        const totalPontos = pontos;
        const totalXp = xp;
        document.getElementById('gameOver').classList.remove('hidden');
        document.getElementById('finalPontos').textContent = totalPontos;
        document.getElementById('finalAcertos').textContent = acertos;
        document.getElementById('finalErros').textContent = erros;

        fetch('/igor_tcc_teste/api/registrar_resposta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario_id: userId,
                finalizar: true,
                jogo: 'queimada',
                materia_id: materiaId || 1,
                dificuldade,
                pontuacao: totalPontos,
                xp_ganho: totalXp,
                acertos,
                erros,
                total_questoes: questoes.length,
                tempo: 0,
                ano_escolar: anoEscolar
            })
        }).catch(() => {});
    }

    function responderComJogador(event) {
        const personagem = event.currentTarget;
        if (turnLocked || personagem.disabled || personagem.dataset.jogador !== jogadorDaVez) return;
        const resposta = personagem.dataset.resposta;
        const q = questoes[indice];
        if (!q) return;
        registrarResposta({ resposta, jogador: personagem.dataset.jogador }, resposta === q.resposta_correta);
    }

    document.getElementById('sortearTimes').addEventListener('click', montarTimes);
    document.getElementById('iniciarPartida').addEventListener('click', () => {
        document.getElementById('teamSetup').classList.add('d-none');
        document.querySelector('.game-area').classList.remove('d-none');
        montarTimes();
        mostrarQuestao();
    });

    document.querySelector('.game-area').classList.add('d-none');
    montarTimes();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
