-- Adiciona fases a um banco existente sem apagar os dados dos alunos.
USE mathplay;

DELIMITER //
DROP PROCEDURE IF EXISTS migrar_progresso//
CREATE PROCEDURE migrar_progresso()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'fase'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN fase INT NOT NULL DEFAULT 1 AFTER nivel;
    END IF;

END//
DELIMITER ;

CALL migrar_progresso();
DROP PROCEDURE migrar_progresso;

CREATE TABLE IF NOT EXISTS fases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    xp_min INT NOT NULL DEFAULT 0,
    xp_max INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO fases (nome, xp_min, xp_max)
SELECT dados.nome, dados.xp_min, dados.xp_max
FROM (
    SELECT 'Iniciante' AS nome, 0 AS xp_min, 499 AS xp_max
    UNION ALL SELECT 'Explorador', 500, 999
    UNION ALL SELECT 'Desafiador', 1000, 1499
    UNION ALL SELECT 'Estratégico', 1500, 1999
    UNION ALL SELECT 'Galático', 2000, 2499
    UNION ALL SELECT 'Lendário', 2500, 999999
) AS dados
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE fases.nome = dados.nome);

UPDATE usuarios SET fase = FLOOR(xp / 500) + 1;