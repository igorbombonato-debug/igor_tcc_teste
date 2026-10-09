-- Remove o sistema de moedas e a loja antiga sem alterar XP, pontos ou fase.
USE mathplay;

DROP TABLE IF EXISTS usuario_itens;
DROP TABLE IF EXISTS itens;

DELIMITER //
DROP PROCEDURE IF EXISTS remover_sistema_moedas//
CREATE PROCEDURE remover_sistema_moedas()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'moedas'
    ) THEN
        ALTER TABLE usuarios DROP COLUMN moedas;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'fases' AND column_name = 'bonus_moedas'
    ) THEN
        ALTER TABLE fases DROP COLUMN bonus_moedas;
    END IF;
END//
DELIMITER ;

CALL remover_sistema_moedas();
DROP PROCEDURE remover_sistema_moedas;