-- Cria a tabela necessária para os pedidos de redefinição de senha em um banco já existente.
-- Execute depois de garantir que a tabela usuarios já foi criada.
CREATE TABLE IF NOT EXISTS recuperacao_senhas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recuperacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
