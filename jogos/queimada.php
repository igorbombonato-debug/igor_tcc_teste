<?php
// Prepara a partida de equipes e fornece perguntas da série do aluno ao navegador.
// Carrega autenticação e funções necessárias para iniciar o jogo.
require_once __DIR__ . '/../includes/auth.php';
require_student();

// Obtém o aluno, o ano, a dificuldade e a matéria escolhida.
$user = get_logged_user();
$pageTitle = 'Queimada Matemática | Mathematics Education';

$ano = (int) ($user['ano_escolar'] ?? 6);
$dificuldade = $_GET['dificuldade'] ?? 'Médio';
$materias = obter_materias($ano);
$materiaIds = array_map('intval', array_column($materias, 'id'));
$materiaSolicitada = (int) ($_GET['materia_id'] ?? 0);
$materiaId = in_array($materiaSolicitada, $materiaIds, true)
    ? $materiaSolicitada
    : (int) ($materiaIds[0] ?? 0);

// Busca questões da matéria antes de completar as dez rodadas.
$questoes = obter_questoes_por_ano($ano, $dificuldade, $materiaId ?: null);
if (empty($questoes)) {
    // Se a matéria ainda não tiver perguntas, usa qualquer pergunta da mesma série.
    $questoes = obter_questoes_por_ano($ano);
}

// Garante dez rodadas, usando questões do mesmo ano se a matéria tiver poucas.
if (count($questoes) < 10) {
    // Completa o conjunto com perguntas de outras matérias, sem repetir IDs.
    $questoesPorAno = obter_questoes_por_ano($ano, $dificuldade);
    $idsExistentes = array_column($questoes, 'id');
    foreach ($questoesPorAno as $questao) {
        if (!in_array($questao['id'], $idsExistentes, true)) {
            $questoes[] = $questao;
            $idsExistentes[] = $questao['id'];
        }
        if (count($questoes) >= 10) {
            break;
        }
    }
}

if (count($questoes) < 10) {
    // Se ainda faltar quantidade, completa com perguntas de qualquer dificuldade.
    $questoesPorAno = obter_questoes_por_ano($ano);
    $idsExistentes = array_column($questoes, 'id');
    foreach ($questoesPorAno as $questao) {
        if (!in_array($questao['id'], $idsExistentes, true)) {
            $questoes[] = $questao;
            $idsExistentes[] = $questao['id'];
        }
        if (count($questoes) >= 10) {
            break;
        }
    }
}

shuffle($questoes);
$questoes = array_slice($questoes, 0, 10);

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
<link rel="stylesheet" href="/igor_tcc_teste/assets/css/jogos.css?v=8">
<!-- Os nomes são coletados antes de mostrar a área de perguntas da partida. -->
<div class="game-shell">
        <section class="team-setup card-glass" id="teamSetup">
            <div class="setup-copy">
                <span class="section-badge">Antes de jogar</span>
                <h1>Monte sua equipe</h1>
                <p>Digite os nomes dos jogadores. Eles serão divididos entre os dois times.</p>
            </div>
            <div class="setup-players" id="setupPlayers">
                <?php foreach (['Jogador 1', 'Jogador 2', 'Jogador 3', 'Jogador 4', 'Jogador 5', 'Jogador 6', 'Jogador 7', 'Jogador 8'] as $indice => $placeholder): ?>
                    <label class="setup-player-field">
                        <span>Jogador <?php echo $indice + 1; ?></span>
                        <input type="text" class="nome-jogador-input" maxlength="30" placeholder="<?php echo $placeholder; ?>" autocomplete="off">
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="setup-preview" id="setupPreview" aria-live="polite"></div>
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
            <div class="hud-item"><span>Cronômetro</span><strong id="tempo">60</strong>s</div>
            <div class="hud-item"><span>Progresso</span><strong id="progresso">0%</strong></div>
        </div>

        <div class="game-area card-glass">
            <div class="question-box">
                <div id="feedback" class="feedback-box hidden" role="status" aria-live="assertive"></div>
                <h2 id="enunciado"><?php echo e($questaoAtual['enunciado'] ?? 'Carregando pergunta...'); ?></h2>
            </div>

            <div class="turn-indicator" id="turnIndicator">Prepare os times para começar.</div>
            <div class="active-player-card" id="activePlayerCard">
                <span class="section-badge">Jogador da vez</span>
                <strong id="jogadorDaVezNome">-</strong>
                <small id="jogadorDaVezTime">-</small>
            </div>
            <div class="answer-area">
                <div class="answer-options" id="answerOptions"></div>
                <form id="answerForm" class="answer-form">
                    <label for="respostaInput">Digite a letra ou o texto da resposta</label>
                    <div class="answer-input-row">
                        <input id="respostaInput" type="text" maxlength="255" autocomplete="off" placeholder="Ex.: A ou resposta">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-send-fill"></i> Responder</button>
                    </div>
                </form>
            </div>
            <div class="quadra" aria-label="Times da queimada matemática">
                <section class="time-panel time-azul">
                    <div class="time-heading"><span class="team-dot"></span><div><strong>Time Azul</strong><small>Jogadores disponíveis</small></div></div>
                    <div class="jogadores" id="timeAzul"></div>
                </section>
                <div class="quadra-divider"><span>VS</span></div>
                <section class="time-panel time-vermelho">
                    <div class="time-heading"><span class="team-dot"></span><div><strong>Time Vermelho</strong><small>Jogadores disponíveis</small></div></div>
                    <div class="jogadores" id="timeVermelho"></div>
                </section>
            </div>
        </div>

        <div id="gameOver" class="game-over-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="resultadoTitulo" tabindex="-1">
            <section class="game-over">
                <h2 id="resultadoTitulo">Fim da partida</h2>
                <div class="winner-box"><span>Vencedor</span><strong id="vencedor">-</strong></div>
                <div class="team-scoreboard">
                    <div class="team-score team-score-blue"><span>Time Azul</span><strong id="pontosAzulFinal">0</strong><small>pontos</small></div>
                    <div class="team-score team-score-red"><span>Time Vermelho</span><strong id="pontosVermelhoFinal">0</strong><small>pontos</small></div>
                </div>
            <p>Pontuação: <strong id="finalPontos">0</strong></p>
            <p>Acertos: <strong id="finalAcertos">0</strong></p>
            <p>Erros: <strong id="finalErros">0</strong></p>
            <p id="salvamentoStatus" class="alert alert-warning hidden" role="status"></p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/igor_tcc_teste/jogos/queimada.php" class="btn btn-primary">JOGAR NOVAMENTE</a>
                <a href="/igor_tcc_teste/public/dashboard.php" class="btn btn-outline-primary">VOLTAR AO MENU</a>
            </div>
            </section>
        </div>
    </div>
</div>

<script>
    // Envia as questões PHP para o JavaScript controlar a partida.
    const questoes = <?php echo json_encode($questoes, JSON_UNESCAPED_UNICODE); ?>;
    const anoEscolar = <?php echo (int) $user['ano_escolar']; ?>;
    const materiaId = <?php echo $materiaId; ?>;
    const dificuldade = <?php echo json_encode($dificuldade); ?>;
    // Estado acumulado da partida.
    let indice = 0;
    let pontos = 0;
    let xp = 0;
    let acertos = 0;
    let erros = 0;
    let tempo = 60;
    let timer = null;
    let combo = 0;
    let turnLocked = false;
        let partidaFinalizada = false;
        const registrosPendentes = [];
        const pontosTimes = { azul: 0, vermelho: 0 };
        let timeEliminadoFinal = null;
    // A primeira rodada começa no Time Azul e alterna após cada resposta.
    let timeDaVez = 'azul';
    const jogadoresEliminados = new Set();
    let times = { azul: [], vermelho: [] };
    let jogadorDaVez = null;
    let jogadorDaVezNome = '';
    // Embaralha os nomes antes de dividir os times.
    function embaralhar(lista) {
        return [...lista].sort(() => Math.random() - 0.5);
    }

    // Valida e coleta os oito nomes digitados na tela inicial.
    function obterNomesJogadores() {
        const nomes = [...document.querySelectorAll('.nome-jogador-input')]
            .map(input => input.value.trim())
            .filter(Boolean);

        if (nomes.length !== 8) {
            alert('Preencha os 8 nomes dos jogadores antes de continuar.');
            return null;
        }

        return nomes;
    }

    function escaparHtml(texto) {
        // Converte os nomes inseridos em texto seguro antes de montar HTML dinâmico.
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
    }

    // Divide os nomes informados em dois grupos de quatro jogadores.
    function montarTimes() {
        const nomes = obterNomesJogadores();
        if (!nomes) return false;

        const jogadores = embaralhar(nomes);
        times = { azul: jogadores.slice(0, 4), vermelho: jogadores.slice(4, 8) };
        document.getElementById('setupPreview').innerHTML = `
            <div class="setup-team setup-team-blue"><strong>Time Azul</strong><span>${times.azul.map(escaparHtml).join(' • ')}</span></div>
            <div class="setup-team setup-team-red"><strong>Time Vermelho</strong><span>${times.vermelho.map(escaparHtml).join(' • ')}</span></div>`;
        return true;
    }

    // Desenha os nomes dos dois times e marca os jogadores eliminados.
    function renderizarTimes() {
        ['azul', 'vermelho'].forEach(time => {
            document.getElementById(`time${time[0].toUpperCase()}${time.slice(1)}`).innerHTML = times[time].map((nome, index) => {
                const id = `${time}-${index}`;
                const eliminado = jogadoresEliminados.has(id);
                return `<div class="personagem ${eliminado ? 'eliminado' : ''}" data-jogador="${id}"><i class="bi bi-person-fill avatar"></i><strong class="nome-jogador">${escaparHtml(nome)}</strong><span class="resposta">${eliminado ? 'ELIMINADO' : 'Disponível'}</span></div>`;
            }).join('');
        });
    }

    // Sorteia um jogador vivo do time que recebeu a pergunta.
    function prepararJogadorDaVez() {
        const jogadoresDoTime = times[timeDaVez].map((_, index) => `${timeDaVez}-${index}`);
        const vivos = jogadoresDoTime.filter(id => !jogadoresEliminados.has(id));
        if (!vivos.length) return false;
        jogadorDaVez = vivos[Math.floor(Math.random() * vivos.length)];
        jogadorDaVezNome = times[timeDaVez][Number(jogadorDaVez.split('-')[1])];
        document.getElementById('jogadorDaVezNome').textContent = jogadorDaVezNome;
        document.getElementById('jogadorDaVezTime').textContent = `Time ${timeDaVez === 'azul' ? 'Azul' : 'Vermelho'}`;
        document.getElementById('turnIndicator').textContent = `Time ${timeDaVez === 'azul' ? 'Azul' : 'Vermelho'} responde agora.`;
        return true;
    }

    // Atualiza pontos, XP, vidas, rodada, tempo e progresso na interface.
    function atualizarHud() {
        document.getElementById('pontos').textContent = pontos;
        document.getElementById('xp').textContent = xp;
        document.getElementById('vidas').textContent = times[timeDaVez].filter((_, index) => !jogadoresEliminados.has(`${timeDaVez}-${index}`)).length;
        document.getElementById('questaoAtual').textContent = Math.min(indice + 1, 10);
        document.getElementById('tempo').textContent = tempo;
        document.getElementById('progresso').textContent = Math.round((indice / 10) * 100) + '%';
    }

    // Exibe a próxima pergunta e inicia o cronômetro de um minuto.
    function mostrarQuestao() {
        if (indice >= questoes.length) {
            finalizarJogo();
            return;
        }
        const q = questoes[indice];
        document.getElementById('enunciado').textContent = q.enunciado;
        const respostas = { A: q.alternativa_a, B: q.alternativa_b, C: q.alternativa_c, D: q.alternativa_d };
        document.getElementById('answerOptions').innerHTML = Object.entries(respostas).map(([letra, texto]) => `<div class="answer-option"><strong>${letra}</strong><span>${escaparHtml(texto)}</span></div>`).join('');
        if (!prepararJogadorDaVez()) {
            finalizarJogo();
            return;
        }
        renderizarTimes();
        document.getElementById('respostaInput').value = '';
        document.getElementById('respostaInput').focus();
        document.getElementById('feedback').classList.add('hidden');
        document.getElementById('feedback').textContent = '';
        document.getElementById('feedback').classList.remove('success', 'error');
        tempo = 60;
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

    // Calcula o resultado local e envia a resposta para a API.
    function registrarResposta(respostaUsuario, correta) {
        const q = questoes[indice];
        if (!q || turnLocked) return;
        const alternativas = { A: q.alternativa_a, B: q.alternativa_b, C: q.alternativa_c, D: q.alternativa_d };
        const letraCorreta = q.resposta_correta.toUpperCase();
        turnLocked = true;
        const timeResposta = timeDaVez;

        const jogadorSelecionado = respostaUsuario.jogador
            ? document.querySelector(`.personagem[data-jogador="${CSS.escape(respostaUsuario.jogador)}"]`)
            : null;
        if (correta) {
            // O valor base depende da dificuldade; a sequência de acertos acrescenta bônus.
            combo += 1;
            acertos += 1;
            const base = q.dificuldade === 'Difícil' ? 180 : q.dificuldade === 'Médio' ? 140 : 100;
            const bonus = combo * 50;
            pontos += base + bonus;
                pontosTimes[timeResposta] += base + bonus;
            xp += q.dificuldade === 'Difícil' ? 30 : q.dificuldade === 'Médio' ? 20 : 15;
            document.getElementById('feedback').innerHTML = '<span class="feedback-icon" aria-hidden="true">✓</span><span><strong>RESPOSTA CORRETA!</strong><small>Excelente! Continue assim.</small></span>';
            document.getElementById('feedback').classList.remove('hidden');
            document.getElementById('feedback').classList.remove('error');
            document.getElementById('feedback').classList.add('success');
            jogadorSelecionado?.classList.add('acertou');
        } else {
            // Uma resposta errada elimina o jogador sorteado para esta rodada.
            combo = 0;
            erros += 1;
            const textoResposta = respostaUsuario.resposta
                ? `Resposta certa: ${letraCorreta}. ${alternativas[letraCorreta]}`
                : `Tempo esgotado! Resposta certa: ${letraCorreta}. ${alternativas[letraCorreta]}`;
            document.getElementById('feedback').innerHTML = `<span class="feedback-icon" aria-hidden="true">✕</span><span><strong>RESPOSTA INCORRETA</strong><small>${escaparHtml(textoResposta)}</small></span>`;
            document.getElementById('feedback').classList.remove('hidden');
            document.getElementById('feedback').classList.remove('success');
            document.getElementById('feedback').classList.add('error');
            jogadoresEliminados.add(jogadorDaVez);
        }

        clearInterval(timer);
        indice += 1;
        const jogadoresRestantes = times[timeResposta].filter((_, index) => !jogadoresEliminados.has(`${timeResposta}-${index}`)).length;
        const timeEliminado = jogadoresRestantes === 0;
        if (timeEliminado) timeEliminadoFinal = timeResposta;
        // Cada pergunta pertence a apenas um time; a próxima sempre alterna o turno.
        timeDaVez = timeDaVez === 'azul' ? 'vermelho' : 'azul';
        atualizarHud();

        setTimeout(() => {
            if (indice >= 10 || timeEliminado) {
                finalizarJogo();
            } else {
                turnLocked = false;
                mostrarQuestao();
            }
        }, 700);

        const registro = fetch('/igor_tcc_teste/api/registrar_resposta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                questao_id: q.id,
                // A primeira resposta da rodada zero inicia um novo registro de partida.
                nova_partida: indice === 1,
                resposta_usuario: respostaUsuario.resposta,
                questao_enunciado: q.enunciado,
                resposta_correta: `${letraCorreta}. ${alternativas[letraCorreta]}`,
                jogador_nome: jogadorDaVezNome || 'Tempo esgotado',
                time_jogador: timeResposta,
                correta: correta ? 1 : 0,
                tempo_resposta: 60 - tempo,
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
        }).then(async response => {
            if (!response.ok) throw new Error('Falha ao salvar uma resposta.');
            const resultado = await response.json();
            if (!resultado.success) throw new Error(resultado.message || 'Falha ao salvar uma resposta.');
        });
        registrosPendentes.push(registro);
    }

    // Mostra o vencedor e envia os totais finais ao ranking.
    function finalizarJogo() {
        if (partidaFinalizada) return;
        partidaFinalizada = true;
        clearInterval(timer);
        const totalPontos = pontos;
        const totalXp = xp;
        const resultadoModal = document.getElementById('gameOver');
        const resultadoCartao = resultadoModal.querySelector('.game-over');
        resultadoModal.classList.remove('hidden');
        document.body.classList.add('game-result-open');
        resultadoCartao.scrollTop = 0;
        resultadoModal.focus({ preventScroll: true });
        const vencedor = timeEliminadoFinal
            ? (timeEliminadoFinal === 'azul' ? 'Time Vermelho' : 'Time Azul')
            : pontosTimes.azul === pontosTimes.vermelho
            ? 'Empate'
            : pontosTimes.azul > pontosTimes.vermelho ? 'Time Azul' : 'Time Vermelho';
        document.getElementById('vencedor').textContent = vencedor;
        document.getElementById('resultadoTitulo').textContent = vencedor === 'Empate' ? 'Partida empatada' : `${vencedor} venceu!`;
        document.getElementById('pontosAzulFinal').textContent = pontosTimes.azul;
        document.getElementById('pontosVermelhoFinal').textContent = pontosTimes.vermelho;
        document.getElementById('finalPontos').textContent = totalPontos;
        document.getElementById('finalAcertos').textContent = acertos;
        document.getElementById('finalErros').textContent = erros;

        // Aguarda todos os envios de respostas antes de salvar o resumo final da partida.
        Promise.allSettled(registrosPendentes)
            .then(() => fetch('/igor_tcc_teste/api/registrar_resposta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    finalizar: true,
                    jogo: 'queimada',
                    materia_id: materiaId,
                    dificuldade,
                    pontuacao: totalPontos,
                    xp_ganho: totalXp,
                    acertos,
                    erros,
                    total_questoes: 10,
                    tempo: 0,
                    equipe_nomes: times,
                    ano_escolar: anoEscolar
                })
            }))
            .then(async response => {
                if (!response.ok) throw new Error('Falha ao salvar o resultado da partida.');
                const resultado = await response.json();
                if (!resultado.success) throw new Error(resultado.message || 'Falha ao salvar o resultado da partida.');
                const respostasComErro = registrosPendentes.length > 0
                    && (await Promise.allSettled(registrosPendentes)).some(item => item.status === 'rejected');
                if (respostasComErro) {
                    throw new Error('O resultado foi salvo, mas algumas respostas não puderam ser registradas.');
                }
                if (resultado.fase_alcancada) {
                    window.mostrarCelebracaoFase(resultado.fase_alcancada);
                }
            })
            .catch(error => {
                const status = document.getElementById('salvamentoStatus');
                status.textContent = error.message;
                status.classList.remove('hidden');
            });
    }

    function normalizarResposta(valor) {
        // Ignora diferenças de maiúsculas, acentos e espaços nas respostas digitadas.
        return valor.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function responderComJogador(event) {
        event.preventDefault();
        if (turnLocked) return;
        const q = questoes[indice];
        if (!q) return;
        const respostaDigitada = normalizarResposta(document.getElementById('respostaInput').value);
        const alternativas = { A: q.alternativa_a, B: q.alternativa_b, C: q.alternativa_c, D: q.alternativa_d };
        const letraCorreta = q.resposta_correta.toUpperCase();
        const respostaCorreta = normalizarResposta(alternativas[letraCorreta]);
        const resposta = Object.keys(alternativas).find(letra => respostaDigitada === letra.toLowerCase() || respostaDigitada === normalizarResposta(alternativas[letra]));
        if (!resposta) {
            document.getElementById('respostaInput').focus();
            return;
        }
        registrarResposta({ resposta, jogador: jogadorDaVez }, resposta === letraCorreta || respostaDigitada === respostaCorreta);
    }

    document.getElementById('sortearTimes').addEventListener('click', montarTimes);
    document.getElementById('iniciarPartida').addEventListener('click', () => {
        if (!montarTimes()) return;
        document.getElementById('teamSetup').classList.add('d-none');
        document.querySelector('.game-area').classList.remove('d-none');
        renderizarTimes();
        mostrarQuestao();
    });
    document.getElementById('answerForm').addEventListener('submit', responderComJogador);

    // A área do jogo só aparece depois de os oito nomes formarem os dois times.
    document.querySelector('.game-area').classList.add('d-none');
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
