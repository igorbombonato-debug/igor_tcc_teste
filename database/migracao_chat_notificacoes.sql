-- Cria conversas privadas e mensagens sem alterar os dados existentes.
USE mathplay;

CREATE TABLE IF NOT EXISTS conversas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_a_id INT NOT NULL,
    usuario_b_id INT NOT NULL,
    criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conversa_participantes (usuario_a_id, usuario_b_id),
    KEY idx_conversas_usuario_b (usuario_b_id, atualizada_em),
    CONSTRAINT fk_conversas_usuario_a FOREIGN KEY (usuario_a_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_conversas_usuario_b FOREIGN KEY (usuario_b_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mensagens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversa_id INT NOT NULL,
    remetente_id INT NOT NULL,
    conteudo VARCHAR(2000) NOT NULL,
    enviada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lida_em TIMESTAMP NULL DEFAULT NULL,
    KEY idx_mensagens_conversa (conversa_id, id),
    KEY idx_mensagens_nao_lidas (conversa_id, remetente_id, lida_em),
    CONSTRAINT fk_mensagens_conversa FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE,
    CONSTRAINT fk_mensagens_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;