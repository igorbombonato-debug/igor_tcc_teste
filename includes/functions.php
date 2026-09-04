<?php

require_once __DIR__ . '/../config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function get_nivel_por_xp(int $xp): int
{
    $nivel = 1;
    $limite = 0;
    $proximo = 500;

    while ($xp >= $proximo) {
        $nivel++;
        $limite = $proximo;
        $proximo += 500;
    }

    return $nivel;
}

function get_xp_proximo_nivel(int $xp): int
{
    $nivel = get_nivel_por_xp($xp);
    $meta = $nivel * 500;
    return max($meta - $xp, 0);
}

function get_nivel_threshold(int $nivel): int
{
    return max(($nivel - 1) * 500, 0);
}

function calcular_percentual(int $acertos, int $total): int
{
    if ($total <= 0) {
        return 0;
    }

    return (int) round(($acertos / $total) * 100);
}

function classificar_desempenho(float $percentual): string
{
    if ($percentual >= 80) {
        return 'DOMINA';
    }

    if ($percentual >= 60) {
        return 'EM DESENVOLVIMENTO';
    }

    if ($percentual >= 40) {
        return 'PRECISA PRATICAR';
    }

    return 'PRECISA DE ATENÇÃO';
}

function obter_materias($ano = null): array
{
    global $pdo;

    $sql = 'SELECT m.*
            FROM materias m
            INNER JOIN (
                SELECT MIN(id) AS id
                FROM materias
                WHERE ativo = 1
                GROUP BY ano_escolar, nome
            ) unicas ON unicas.id = m.id';
    $params = [];

    if ($ano !== null) {
        $sql .= ' AND ano_escolar = :ano';
        $params['ano'] = $ano;
    }

    $sql .= ' ORDER BY nome ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function obter_questoes_por_ano($ano, $dificuldade = null, $materiaId = null): array
{
    global $pdo;

    $sql = 'SELECT * FROM questoes WHERE ativo = 1 AND ano_escolar = :ano';
    $params = ['ano' => $ano];

    if ($dificuldade) {
        $sql .= ' AND dificuldade = :dificuldade';
        $params['dificuldade'] = $dificuldade;
    }

    if ($materiaId) {
        $sql .= ' AND materia_id = :materia_id';
        $params['materia_id'] = $materiaId;
    }

    $sql .= ' ORDER BY RAND()';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function buscar_usuario_por_email(string $email): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    return $stmt->fetch() ?: null;
}

function buscar_usuario_por_username(string $username): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    return $stmt->fetch() ?: null;
}

function registrar_partida(array $dados): int
{
    global $pdo;

    $sql = 'INSERT INTO partidas (usuario_id, jogo, materia_id, dificuldade, pontuacao, xp_ganho, acertos, erros, total_questoes, tempo, created_at)
            VALUES (:usuario_id, :jogo, :materia_id, :dificuldade, :pontuacao, :xp_ganho, :acertos, :erros, :total_questoes, :tempo, NOW())';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'usuario_id' => $dados['usuario_id'],
        'jogo' => $dados['jogo'],
        'materia_id' => $dados['materia_id'],
        'dificuldade' => $dados['dificuldade'],
        'pontuacao' => $dados['pontuacao'],
        'xp_ganho' => $dados['xp_ganho'],
        'acertos' => $dados['acertos'],
        'erros' => $dados['erros'],
        'total_questoes' => $dados['total_questoes'],
        'tempo' => $dados['tempo'],
    ]);

    return (int) $pdo->lastInsertId();
}

function atualizar_xp_e_nivel(int $usuarioId, int $xpGanho): void
{
    global $pdo;

    $usuario = $pdo->prepare('SELECT xp, nivel, pontos FROM usuarios WHERE id = :id LIMIT 1');
    $usuario->execute(['id' => $usuarioId]);
    $dados = $usuario->fetch();

    if (!$dados) {
        return;
    }

    $novoXp = (int) $dados['xp'] + $xpGanho;
    $novoNivel = get_nivel_por_xp($novoXp);

    $stmt = $pdo->prepare('UPDATE usuarios SET xp = :xp, nivel = :nivel WHERE id = :id');
    $stmt->execute([
        'xp' => $novoXp,
        'nivel' => $novoNivel,
        'id' => $usuarioId,
    ]);
}

function atualizar_pontos(int $usuarioId, int $pontos): void
{
    global $pdo;
    $stmt = $pdo->prepare('UPDATE usuarios SET pontos = pontos + :pontos WHERE id = :id');
    $stmt->execute(['pontos' => $pontos, 'id' => $usuarioId]);
}

function atualizar_desempenho_usuario(int $usuarioId, int $materiaId, int $acertos, int $erros): void
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM desempenho WHERE usuario_id = :usuario_id AND materia_id = :materia_id LIMIT 1');
    $stmt->execute(['usuario_id' => $usuarioId, 'materia_id' => $materiaId]);
    $registro = $stmt->fetch();

    if ($registro) {
        $novasPartidas = (int) $registro['partidas'] + 1;
        $novosAcertos = (int) $registro['acertos'] + $acertos;
        $novosErros = (int) $registro['erros'] + $erros;
        $percentual = calcular_percentual($novosAcertos, $novosAcertos + $novosErros);
        $classificacao = classificar_desempenho($percentual);

        $update = $pdo->prepare('UPDATE desempenho SET partidas = :partidas, acertos = :acertos, erros = :erros, percentual = :percentual, classificacao = :classificacao, updated_at = NOW() WHERE id = :id');
        $update->execute([
            'partidas' => $novasPartidas,
            'acertos' => $novosAcertos,
            'erros' => $novosErros,
            'percentual' => $percentual,
            'classificacao' => $classificacao,
            'id' => $registro['id'],
        ]);
        return;
    }

    $total = $acertos + $erros;
    $percentual = calcular_percentual($acertos, $total);
    $classificacao = classificar_desempenho($percentual);

    $insert = $pdo->prepare('INSERT INTO desempenho (usuario_id, materia_id, partidas, acertos, erros, percentual, classificacao, updated_at) VALUES (:usuario_id, :materia_id, 1, :acertos, :erros, :percentual, :classificacao, NOW())');
    $insert->execute([
        'usuario_id' => $usuarioId,
        'materia_id' => $materiaId,
        'acertos' => $acertos,
        'erros' => $erros,
        'percentual' => $percentual,
        'classificacao' => $classificacao,
    ]);
}

function obter_ranking_geral(int $limit = 10): array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.*, @rank := @rank + 1 as pos FROM usuarios u, (SELECT @rank := 0) r WHERE u.ativo = 1 AND u.tipo = "aluno" ORDER BY u.pontos DESC, u.xp DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function obter_ranking_por_ano(int $ano, int $limit = 10): array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE ativo = 1 AND tipo = "aluno" AND ano_escolar = :ano ORDER BY pontos DESC, xp DESC LIMIT :limit');
    $stmt->bindValue(':ano', $ano, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function calcular_percentual_materia(int $usuarioId, int $materiaId): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT percentual FROM desempenho WHERE usuario_id = :usuario_id AND materia_id = :materia_id LIMIT 1');
    $stmt->execute(['usuario_id' => $usuarioId, 'materia_id' => $materiaId]);
    $row = $stmt->fetch();
    return $row ? (int) $row['percentual'] : 0;
}

function get_usuario_resumo(int $usuarioId): array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $usuarioId]);
    return $stmt->fetch();
}

function get_materia_nome(int $materiaId): string
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT nome FROM materias WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $materiaId]);
    $row = $stmt->fetch();
    return $row['nome'] ?? 'Geral';
}
