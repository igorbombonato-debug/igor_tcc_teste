-- Atualiza uma instalação já existente para aceitar contas de professores e registros de resposta flexíveis.
-- Execute esta migração uma única vez depois de criar o banco com a versão anterior do esquema.
-- Execute uma vez no banco existente da aplicação.
ALTER TABLE usuarios
    MODIFY tipo ENUM('aluno', 'professor', 'admin') NOT NULL DEFAULT 'aluno';

-- Respostas sem uma questão vinculada podem continuar no histórico usando os textos salvos no momento da partida.
ALTER TABLE respostas
    DROP FOREIGN KEY fk_respostas_questao,
    MODIFY questao_id INT DEFAULT NULL,
    MODIFY resposta_usuario VARCHAR(255) DEFAULT NULL,
    ADD enunciado_snapshot TEXT DEFAULT NULL,
    ADD resposta_correta VARCHAR(255) DEFAULT NULL;

ALTER TABLE respostas
    ADD CONSTRAINT fk_respostas_questao FOREIGN KEY (questao_id) REFERENCES questoes(id) ON DELETE SET NULL;
