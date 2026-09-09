-- Cria o banco usando UTF-8 para preservar acentos.
CREATE DATABASE IF NOT EXISTS mathplay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Seleciona o banco que receberá as tabelas abaixo.
USE mathplay;

-- Remove tabelas antigas para permitir uma instalação limpa.
DROP TABLE IF EXISTS usuario_conquistas;
DROP TABLE IF EXISTS conquistas;
DROP TABLE IF EXISTS respostas;
DROP TABLE IF EXISTS desempenho;
DROP TABLE IF EXISTS partidas;
DROP TABLE IF EXISTS questoes;
DROP TABLE IF EXISTS materias;
DROP TABLE IF EXISTS recuperacao_senhas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS configuracoes;

-- Guarda os dados de login, série, XP, nível e pontuação dos alunos.
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('aluno', 'admin') NOT NULL DEFAULT 'aluno',
    ano_escolar TINYINT NOT NULL,
    xp INT NOT NULL DEFAULT 0,
    nivel INT NOT NULL DEFAULT 1,
    pontos INT NOT NULL DEFAULT 0,
    avatar VARCHAR(255) DEFAULT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_materias_ano_nome (ano_escolar, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Armazena tokens temporários usados para redefinir senhas.
CREATE TABLE recuperacao_senhas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recuperacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Guarda as matérias disponíveis por série escolar.
CREATE TABLE materias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    ano_escolar TINYINT NOT NULL,
    icone VARCHAR(100) DEFAULT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Guarda perguntas, alternativas e respostas corretas.
CREATE TABLE questoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    materia_id INT NOT NULL,
    ano_escolar TINYINT NOT NULL,
    dificuldade ENUM('Fácil','Médio','Difícil') NOT NULL,
    enunciado TEXT NOT NULL,
    alternativa_a VARCHAR(255) NOT NULL,
    alternativa_b VARCHAR(255) NOT NULL,
    alternativa_c VARCHAR(255) NOT NULL,
    alternativa_d VARCHAR(255) NOT NULL,
    resposta_correta CHAR(1) NOT NULL,
    explicacao TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_questoes_materia FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Guarda o resumo de cada partida e seus resultados.
CREATE TABLE partidas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    jogo VARCHAR(100) NOT NULL,
    materia_id INT DEFAULT NULL,
    ano_escolar TINYINT DEFAULT NULL,
    dificuldade VARCHAR(20) DEFAULT 'Médio',
    pontuacao INT NOT NULL DEFAULT 0,
    xp_ganho INT NOT NULL DEFAULT 0,
    acertos INT NOT NULL DEFAULT 0,
    erros INT NOT NULL DEFAULT 0,
    total_questoes INT NOT NULL DEFAULT 0,
    tempo INT NOT NULL DEFAULT 0,
    equipe_nomes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_partidas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_partidas_materia FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Guarda cada questão respondida dentro de uma partida.
CREATE TABLE respostas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    partida_id INT NOT NULL,
    questao_id INT NOT NULL,
    resposta_usuario CHAR(1) DEFAULT NULL,
    jogador_nome VARCHAR(100) DEFAULT NULL,
    time_jogador ENUM('azul', 'vermelho') DEFAULT NULL,
    correta TINYINT(1) NOT NULL DEFAULT 0,
    tempo_resposta INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_respostas_partida FOREIGN KEY (partida_id) REFERENCES partidas(id) ON DELETE CASCADE,
    CONSTRAINT fk_respostas_questao FOREIGN KEY (questao_id) REFERENCES questoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Consolida o desempenho do aluno por matéria.
CREATE TABLE desempenho (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    materia_id INT NOT NULL,
    partidas INT NOT NULL DEFAULT 0,
    acertos INT NOT NULL DEFAULT 0,
    erros INT NOT NULL DEFAULT 0,
    percentual INT NOT NULL DEFAULT 0,
    classificacao VARCHAR(50) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_desempenho_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_desempenho_materia FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conquistas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    icone VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuario_conquistas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    conquista_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_conquistas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_conquistas_conquista FOREIGN KEY (conquista_id) REFERENCES conquistas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO materias (nome, descricao, ano_escolar, icone, ativo) VALUES
('Números naturais', 'Estudo dos números inteiros positivos e suas propriedades.', 6, 'bi-123', 1),
('Números inteiros', 'Trabalho com números positivos e negativos.', 6, 'bi-plus-slash-minus', 1),
('Operações básicas', 'Adição, subtração, multiplicação e divisão.', 6, 'bi-calculator', 1),
('Expressões numéricas', 'Sequência correta de operações matemáticas.', 6, 'bi-function', 1),
('Frações', 'Representação e operações com frações.', 6, 'bi-pie-chart', 1),
('Números decimais', 'Valores com casas decimais.', 6, 'bi-0-circle', 1),
('Múltiplos', 'Múltiplos de um número natural.', 6, 'bi-dice-6', 1),
('Divisores', 'Divisores e divisibilidade.', 6, 'bi-collection', 1),
('MMC', 'Mínimo múltiplo comum.', 6, 'bi-diagram-3', 1),
('MDC', 'Máximo divisor comum.', 6, 'bi-diagram-2', 1),
('Razão', 'Comparação entre duas grandezas.', 6, 'bi-graph-up', 1),
('Proporção', 'Igualdade entre razões.', 6, 'bi-percent', 1),
('Porcentagem', 'Cálculo percentual.', 6, 'bi-percent', 1),
('Perímetro', 'Medida ao redor de figuras geométricas.', 6, 'bi-square', 1),
('Área', 'Medida da superfície de figuras.', 6, 'bi-bounding-box', 1),
('Ângulos', 'Medidas e classificações de ângulos.', 6, 'bi-arrow-up-right', 1),
('Gráficos', 'Leitura e interpretação de gráficos.', 6, 'bi-bar-chart', 1),
('Estatística básica', 'Média, moda e mediana.', 6, 'bi-graph-up-arrow', 1),
('Números racionais', 'Conjunto dos números racionais.', 7, 'bi-1-circle', 1),
('Regra de três', 'Resolução de problemas com proporções.', 7, 'bi-journal-check', 1),
('Expressões algébricas', 'Uso de letras e termos.', 7, 'bi-variable', 1),
('Equação do 1º grau', 'Resolução de equações lineares.', 7, 'bi-equals', 1),
('Triângulos', 'Características e propriedades.', 7, 'bi-triangle', 1),
('Quadriláteros', 'Figuras de quatro lados.', 7, 'bi-square-fill', 1),
('Probabilidade', 'Chances de eventos.', 7, 'bi-shuffle', 1),
('Estatística', 'Análise de dados e tabelas.', 7, 'bi-clipboard-data', 1),
('Potenciação', 'Produto de fatores iguais.', 8, 'bi-power', 1),
('Radiciação', 'Operação inversa da potenciação.', 8, 'bi-root', 1),
('Notação científica', 'Representação compacta de números.', 8, 'bi-activity', 1),
('Produtos notáveis', 'Identidades algébricas importantes.', 8, 'bi-box-seam', 1),
('Fatoração', 'Decomposição em fatores.', 8, 'bi-funnel', 1),
('Equações', 'Equações de diferentes tipos.', 8, 'bi-calculator-fill', 1),
('Sistemas de equações', 'Resolução de sistemas lineares.', 8, 'bi-collection-fill', 1),
('Funções', 'Relação entre grandezas.', 8, 'bi-function', 1),
('Teorema de Pitágoras', 'Relação em triângulos retângulos.', 8, 'bi-rulers', 1),
('Geometria', 'Estudo de formas e medidas.', 8, 'bi-bounding-box-circles', 1),
('Volume', 'Espaço ocupado por sólidos.', 8, 'bi-box', 1),
('Números reais', 'Conjunto dos números reais.', 9, 'bi-0-circle-fill', 1),
('Equação do 2º grau', 'Resolução de equações quadráticas.', 9, 'bi-x-circle', 1),
('Função afim', 'Função linear e crescente/decrescente.', 9, 'bi-graph-down', 1),
('Função quadrática', 'Função do tipo ax² + bx + c.', 9, 'bi-graph-up-arrow', 1),
('Semelhança', 'Figuras semelhantes e proporcionalidade.', 9, 'bi-aspect-ratio', 1),
('Relações métricas', 'Cálculo de medidas em triângulos.', 9, 'bi-rulers', 1),
('Geometria espacial', 'Estudo de sólidos no espaço.', 9, 'bi-boxes', 1),
('Gráficos', 'Interpretação de gráficos e funções.', 9, 'bi-bar-chart-steps', 1);

INSERT INTO conquistas (nome, descricao, icone) VALUES
('Primeira partida', 'Concluiu sua primeira partida.', '🏆'),
('5 acertos seguidos', 'Conseguiu uma sequência de 5 acertos.', '🔥'),
('1000 pontos', 'Alcance 1000 pontos.', '⭐'),
('Mestre das Frações', 'Mostrou domínio em frações.', '🧠'),
('90% de aproveitamento', 'Obteve 90% de aproveitamento em uma partida.', '🎯'),
('10 partidas concluídas', 'Concluiu 10 partidas no total.', '🏅');

INSERT IGNORE INTO materias (nome, descricao, ano_escolar, icone, ativo) VALUES
('Números inteiros', 'Conjunto dos inteiros e suas operações.', 7, 'bi-plus-slash-minus', 1),
('Números racionais', 'Frações e decimais em um mesmo conjunto.', 7, 'bi-1-circle', 1),
('Frações', 'Representação e cálculo de frações.', 7, 'bi-pie-chart', 1),
('Decimais', 'Operações com números decimais.', 7, 'bi-123', 1),
('Porcentagem', 'Cálculo percentual em situações reais.', 7, 'bi-percent', 1),
('Razão', 'Comparação de grandezas.', 7, 'bi-graph-up', 1),
('Proporção', 'Igualdade entre razões.', 7, 'bi-graph-up-arrow', 1),
('Expressões algébricas', 'Cálculo com letras e operações.', 7, 'bi-function', 1),
('Equação do 1º grau', 'Equações lineares.', 7, 'bi-equals', 1),
('Ângulos', 'Classificação e medidas de ângulos.', 7, 'bi-arrow-up-right', 1),
('Triângulos', 'Tipos e propriedades.', 7, 'bi-triangle', 1),
('Quadriláteros', 'Formas de quatro lados.', 7, 'bi-square-fill', 1),
('Área', 'Medida de superfícies.', 7, 'bi-bounding-box', 1),
('Perímetro', 'Medida ao redor da figura.', 7, 'bi-square', 1),
('Probabilidade', 'Cálculo de chances.', 7, 'bi-shuffle', 1),
('Estatística', 'Leitura e análise de dados.', 7, 'bi-clipboard-data', 1),
('Potenciação', 'Multiplicação repetida.', 8, 'bi-power', 1),
('Radiciação', 'Operação inversa da potenciação.', 8, 'bi-root', 1),
('Notação científica', 'Escrita de números muito grandes ou pequenos.', 8, 'bi-activity', 1),
('Produtos notáveis', 'Identidades algébricas.', 8, 'bi-box-seam', 1),
('Fatoração', 'Decomposição em fatores primos.', 8, 'bi-funnel', 1),
('Equações', 'Resolução de equações.', 8, 'bi-calculator-fill', 1),
('Sistemas de equações', 'Soluções de sistemas.', 8, 'bi-collection-fill', 1),
('Funções', 'Mapeamentos entre variáveis.', 8, 'bi-function', 1),
('Razão', 'Comparação proporcional.', 8, 'bi-graph-up', 1),
('Proporção', 'Relação entre grandezas.', 8, 'bi-percent', 1),
('Porcentagem', 'Percentuais em problemas.', 8, 'bi-percent', 1),
('Teorema de Pitágoras', 'Relação nos triângulos retângulos.', 8, 'bi-rulers', 1),
('Geometria', 'Estudo de formas e dimensões.', 8, 'bi-bounding-box-circles', 1),
('Área', 'Área de figuras planas.', 8, 'bi-bounding-box', 1),
('Volume', 'Volume de sólidos.', 8, 'bi-box', 1),
('Estatística', 'Análise de dados.', 8, 'bi-clipboard-data', 1),
('Probabilidade', 'Cálculo probabilístico.', 8, 'bi-shuffle', 1),
('Números reais', 'Conjunto dos números reais.', 9, 'bi-0-circle-fill', 1),
('Potenciação', 'Potências e propriedades.', 9, 'bi-power', 1),
('Radiciação', 'Raízes e propriedades.', 9, 'bi-root', 1),
('Equação do 2º grau', 'Equações quadráticas.', 9, 'bi-x-circle', 1),
('Sistemas de equações', 'Sistemas lineares.', 9, 'bi-collection-fill', 1),
('Funções', 'Estudo de funções.', 9, 'bi-function', 1),
('Função afim', 'Função linear.', 9, 'bi-graph-down', 1),
('Função quadrática', 'Função quadrática.', 9, 'bi-graph-up-arrow', 1),
('Semelhança', 'Proporcionalidade entre figuras.', 9, 'bi-aspect-ratio', 1),
('Teorema de Pitágoras', 'Aplicações do teorema.', 9, 'bi-rulers', 1),
('Relações métricas', 'Medidas em triângulos.', 9, 'bi-rulers', 1),
('Geometria espacial', 'Sólidos geométricos.', 9, 'bi-boxes', 1),
('Área', 'Área de figuras e sólidos.', 9, 'bi-bounding-box', 1),
('Volume', 'Volume de prismas e cilindros.', 9, 'bi-box', 1),
('Estatística', 'Médias e desvios.', 9, 'bi-clipboard-data', 1),
('Probabilidade', 'Eventos e chance.', 9, 'bi-shuffle', 1),
('Gráficos', 'Leitura de gráficos de funções.', 9, 'bi-bar-chart-steps', 1);

CREATE TABLE IF NOT EXISTS perguntas_geradas ( id INT PRIMARY KEY AUTO_INCREMENT, materia_id INT, ano_escolar TINYINT, dificuldade VARCHAR(20), enunciado TEXT, alternativa_a VARCHAR(255), alternativa_b VARCHAR(255), alternativa_c VARCHAR(255), alternativa_d VARCHAR(255), resposta_correta CHAR(1), explicacao TEXT );

INSERT IGNORE INTO materias (nome, descricao, ano_escolar, icone, ativo) VALUES
('Números naturais', 'Estudo dos números inteiros positivos e suas propriedades.', 6, 'bi-123', 1),
('Números inteiros', 'Trabalho com números positivos e negativos.', 6, 'bi-plus-slash-minus', 1),
('Operações básicas', 'Adição, subtração, multiplicação e divisão.', 6, 'bi-calculator', 1),
('Expressões numéricas', 'Sequência correta de operações matemáticas.', 6, 'bi-function', 1),
('Frações', 'Representação e operações com frações.', 6, 'bi-pie-chart', 1),
('Números decimais', 'Valores com casas decimais.', 6, 'bi-0-circle', 1),
('Múltiplos', 'Múltiplos de um número natural.', 6, 'bi-dice-6', 1),
('Divisores', 'Divisores e divisibilidade.', 6, 'bi-collection', 1),
('MMC', 'Mínimo múltiplo comum.', 6, 'bi-diagram-3', 1),
('MDC', 'Máximo divisor comum.', 6, 'bi-diagram-2', 1),
('Razão', 'Comparação entre duas grandezas.', 6, 'bi-graph-up', 1),
('Proporção', 'Igualdade entre razões.', 6, 'bi-percent', 1),
('Porcentagem', 'Cálculo percentual.', 6, 'bi-percent', 1),
('Perímetro', 'Medida ao redor de figuras geométricas.', 6, 'bi-square', 1),
('Área', 'Medida da superfície de figuras.', 6, 'bi-bounding-box', 1),
('Ângulos', 'Medidas e classificações de ângulos.', 6, 'bi-arrow-up-right', 1),
('Gráficos', 'Leitura e interpretação de gráficos.', 6, 'bi-bar-chart', 1),
('Estatística básica', 'Média, moda e mediana.', 6, 'bi-graph-up-arrow', 1);

CREATE TABLE IF NOT EXISTS configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracoes (chave, valor) VALUES
('versao', '1.0'),
('nome_sistema', 'MathPlay');

SET @base_materia_id := 1;

INSERT INTO questoes (materia_id, ano_escolar, dificuldade, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, ativo) VALUES
(1, 6, 'Fácil', 'Qual é o resultado de 15 + 27?', '32', '42', '40', '38', 'B', 'Somando 15 + 27 resulta em 42.', 1),
(1, 6, 'Fácil', 'Qual é o próximo número da sequência: 2, 4, 6, 8, ?', '9', '10', '12', '14', 'B', 'A sequência aumenta de 2 em 2 e o próximo número é 10.', 1),
(1, 6, 'Médio', 'Qual é o valor de 3 × 12?', '26', '30', '36', '42', 'C', '3 × 12 = 36.', 1),
(1, 6, 'Médio', 'Qual é o resultado de 49 ÷ 7?', '6', '7', '8', '9', 'B', '49 dividido por 7 é 7.', 1),
(1, 6, 'Difícil', 'Qual é a soma de 125 + 378?', '393', '403', '493', '503', 'C', '125 + 378 = 503; como 125 + 300 = 425 e 425 + 78 = 503.', 1),
(2, 6, 'Fácil', 'Qual é o valor de -5 + 8?', '3', '-3', '13', '-13', 'A', 'Somando 8 a -5 resulta em 3.', 1),
(2, 6, 'Médio', 'Qual é o resultado de -12 - 4?', '-16', '-8', '8', '16', 'A', 'Subtrair 4 de -12 resulta em -16.', 1),
(2, 6, 'Difícil', 'Qual é o valor de (-7) × (-6)?', '-42', '42', '-13', '13', 'B', 'Produto de dois números negativos é positivo: 42.', 1),
(3, 6, 'Fácil', 'Quanto é 18 + 9?', '25', '26', '27', '28', 'C', '18 + 9 = 27.', 1),
(3, 6, 'Fácil', 'Qual é o resultado de 34 - 19?', '13', '14', '15', '16', 'C', '34 - 19 = 15.', 1),
(3, 6, 'Médio', 'Quanto é 24 × 5?', '100', '110', '115', '120', 'D', '24 × 5 = 120.', 1),
(3, 6, 'Médio', 'Qual é o valor de 72 ÷ 8?', '7', '8', '9', '10', 'C', '72 ÷ 8 = 9.', 1),
(3, 6, 'Difícil', 'Se um pacote tem 12 maçãs e você compra 4 pacotes, quantas maçãs terá?', '36', '42', '48', '54', 'C', '12 × 4 = 48.', 1),
(4, 6, 'Fácil', 'Qual é o valor de 3 + 4 × 2?', '11', '14', '16', '20', 'A', 'Primeiro a multiplicação: 4 × 2 = 8; depois 3 + 8 = 11.', 1),
(4, 6, 'Médio', 'Qual é o resultado de 10 + 6 ÷ 2?', '8', '10', '13', '16', 'C', 'Primeiro 6 ÷ 2 = 3 e depois 10 + 3 = 13.', 1),
(4, 6, 'Difícil', 'Qual é o valor de 5 × (3 + 4)?', '18', '24', '35', '40', 'C', 'Primeiro a soma dentro do parêntese: 3 + 4 = 7; depois 5 × 7 = 35.', 1),
(5, 6, 'Fácil', 'Qual é a fração equivalente a 1/2?', '2/3', '2/4', '3/4', '1/3', 'B', '2/4 também representa metade do total.', 1),
(5, 6, 'Médio', 'Qual é o resultado de 1/2 + 1/2?', '1/2', '1', '2/2', '3/2', 'B', 'Metade mais metade resulta em 1 inteiro.', 1),
(5, 6, 'Difícil', 'Qual é a soma de 3/4 + 1/8?', '4/8', '5/8', '7/8', '1', 'C', '3/4 = 6/8; 6/8 + 1/8 = 7/8.', 1),
(6, 6, 'Fácil', 'Qual é o valor decimal de 1/10?', '0,01', '0,1', '1', '10', 'B', '1/10 equivale a 0,1.', 1),
(6, 6, 'Médio', 'Qual é a soma de 0,5 + 0,25?', '0,60', '0,75', '0,80', '1,00', 'B', '0,5 + 0,25 = 0,75.', 1),
(6, 6, 'Difícil', 'Qual é o resultado de 2,5 × 4?', '8', '9', '10', '12', 'C', '2,5 × 4 = 10.', 1),
(7, 6, 'Fácil', 'Qual é o múltiplo de 6 entre 12 e 24?', '15', '18', '20', '21', 'B', '18 é múltiplo de 6.', 1),
(7, 6, 'Médio', 'Qual é o menor múltiplo comum de 4 e 6?', '8', '12', '18', '24', 'B', '12 é múltiplo de 4 e 6.', 1),
(8, 6, 'Fácil', 'Qual é o divisor de 12?', '5', '7', '6', '11', 'C', '6 divide 12 exatamente.', 1),
(8, 6, 'Médio', 'Qual dos números é divisor de 18?', '7', '9', '11', '13', 'B', '18 ÷ 9 = 2.', 1),
(9, 6, 'Fácil', 'Qual é o MMC de 2 e 3?', '5', '6', '8', '9', 'B', 'O menor múltiplo comum de 2 e 3 é 6.', 1),
(9, 6, 'Médio', 'Qual é o MMC de 4 e 6?', '8', '12', '18', '24', 'B', '12 é o menor múltiplo comum entre 4 e 6.', 1),
(10, 6, 'Fácil', 'Qual é o MDC de 8 e 12?', '2', '4', '6', '8', 'B', '4 é o maior divisor comum entre 8 e 12.', 1),
(10, 6, 'Médio', 'Qual é o MDC de 18 e 24?', '2', '3', '6', '12', 'C', '6 é o maior divisor comum de 18 e 24.', 1),
(11, 6, 'Fácil', 'A razão entre 6 e 3 é:', '1:2', '2:1', '3:1', '6:1', 'B', 'A razão de 6 para 3 é 2:1.', 1),
(11, 6, 'Médio', 'Se a razão é 3:5 e o primeiro termo é 9, qual é o segundo?', '10', '12', '15', '18', 'C', '3:5 = 9:15, porque 9 ÷ 3 = 3 e 5 × 3 = 15.', 1),
(12, 6, 'Fácil', '25% de 80 é:', '10', '15', '20', '25', 'C', '25% de 80 é 20.', 1),
(12, 6, 'Médio', 'Qual é 15% de 200?', '20', '25', '30', '35', 'C', '15% de 200 = 30.', 1),
(13, 6, 'Fácil', 'Qual é o perímetro de um retângulo de lados 4 e 3?', '7', '10', '12', '14', 'D', 'Perímetro = 2 × (4 + 3) = 14.', 1),
(13, 6, 'Médio', 'Um quadrado tem lado 5. Qual é seu perímetro?', '10', '15', '20', '25', 'C', 'Perímetro do quadrado = 4 × 5 = 20.', 1),
(14, 6, 'Fácil', 'Qual é a área de um retângulo 5 × 4?', '9', '18', '20', '24', 'C', 'Área = 5 × 4 = 20.', 1),
(14, 6, 'Médio', 'Qual é a área de um quadrado de lado 7?', '28', '35', '42', '49', 'D', 'Área = 7 × 7 = 49.', 1),
(15, 6, 'Fácil', 'Qual é o ângulo reto?', '30°', '45°', '90°', '120°', 'C', 'O ângulo reto mede 90°.', 1),
(15, 6, 'Médio', 'Qual é o ângulo agudo?', '100°', '80°', '180°', '270°', 'B', 'Um ângulo agudo mede menos de 90°.', 1),
(16, 6, 'Fácil', 'O gráfico de barras serve para:', 'Descobrir área', 'Comparar valores', 'Calcular porcentagem', 'Resolver equações', 'B', 'Gráficos de barras comparam informações entre categorias.', 1),
(16, 6, 'Médio', 'Em um gráfico, a barra mais alta indica:', 'O maior valor', 'O menor valor', 'A média', 'A moda', 'A', 'A maior barra representa o maior valor observado.', 1),
(17, 6, 'Fácil', 'Qual é a média de 2, 4 e 6?', '3', '4', '5', '6', 'B', 'Média = (2 + 4 + 6) ÷ 3 = 4.', 1),
(17, 6, 'Médio', 'Em uma lista de dados, o valor que aparece com mais frequência é:', 'Média', 'Mediana', 'Moda', 'Amplitude', 'C', 'A moda é o valor mais frequente.', 1),
(18, 7, 'Fácil', 'Qual é o valor de -3 + 7?', '4', '-4', '10', '-10', 'A', '7 − 3 = 4.', 1),
(18, 7, 'Médio', 'Qual é o resultado de -8 - (-3)?', '-11', '-5', '5', '11', 'B', 'Subtrair um número negativo equivale a somar o oposto: -8 + 3 = -5.', 1),
(18, 7, 'Difícil', 'Qual é o valor de (-4) × 5 + 8?', '-12', '-20', '12', '20', 'A', '(-4) × 5 = -20, e -20 + 8 = -12.', 1),
(19, 7, 'Fácil', 'Qual é o conjunto dos números racionais?', 'Números inteiros apenas', 'Frações e decimais finitos ou periódicos', 'Números negativos apenas', 'Todos os números da reta', 'B', 'Números racionais podem ser escritos como fração de inteiros.', 1),
(19, 7, 'Médio', 'Qual valor é maior: 1/2 ou 2/3?', '1/2', '2/3', 'São iguais', 'Não dá para comparar', 'B', '2/3 é maior que 1/2.', 1),
(19, 7, 'Difícil', 'Qual é a soma de 1/3 + 1/6?', '1/3', '1/2', '2/3', '5/6', 'B', '1/3 = 2/6, então 2/6 + 1/6 = 3/6 = 1/2.', 1),
(20, 7, 'Fácil', 'Se 3 cadernos custam R$ 18, quanto custa 1 caderno?', 'R$ 4', 'R$ 5', 'R$ 6', 'R$ 8', 'C', '18 ÷ 3 = 6.', 1),
(20, 7, 'Médio', 'Se 5 maçãs custam R$ 15, quanto custam 8 maçãs?', 'R$ 20', 'R$ 24', 'R$ 30', 'R$ 40', 'B', 'Uma maçã custa R$ 3, então 8 custam R$ 24.', 1),
(20, 7, 'Difícil', 'Se 4 operários fazem um trabalho em 6 dias, em quantos dias 8 operários faria o mesmo trabalho?', '2', '3', '4', '6', 'B', 'A quantidade de operários dobra, então o tempo cai pela metade: 3 dias.', 1),
(21, 7, 'Fácil', 'Qual é o valor de x em x + 4 = 9?', '3', '4', '5', '6', 'C', 'x = 9 - 4 = 5.', 1),
(21, 7, 'Médio', 'Qual é a solução de 3x = 18?', '4', '5', '6', '7', 'C', 'x = 18 ÷ 3 = 6.', 1),
(21, 7, 'Difícil', 'Qual é o valor de x em 2x - 5 = 11?', '6', '7', '8', '9', 'B', '2x = 16, então x = 8.', 1),
(22, 7, 'Fácil', 'Qual é o nome do polígono de 3 lados?', 'Quadrado', 'Triângulo', 'Pentágono', 'Hexágono', 'B', 'Triângulo possui 3 lados.', 1),
(22, 7, 'Médio', 'Qual é a soma dos ângulos internos de um triângulo?', '90°', '180°', '270°', '360°', 'B', 'A soma dos ângulos internos de qualquer triângulo é 180°.', 1),
(22, 7, 'Difícil', 'Qual é o tipo de triângulo com todos os lados iguais?', 'Escaleno', 'Isósceles', 'Equilátero', 'Retângulo', 'C', 'Equilátero tem três lados iguais.', 1),
(23, 7, 'Fácil', 'Qual é o nome da figura de 4 lados iguais?', 'Retângulo', 'Quadrado', 'Trapézio', 'Losango', 'B', 'O quadrado tem lados iguais e ângulos retos.', 1),
(23, 7, 'Médio', 'Qual é o paralelogramo com ângulos retos?', 'Trapézio', 'Losango', 'Retângulo', 'Triângulo', 'C', 'O retângulo tem lados opostos paralelos e ângulos retos.', 1),
(23, 7, 'Difícil', 'Qual figura tem diagonais perpendiculares?', 'Retângulo', 'Quadrado', 'Losango', 'Trapézio', 'C', 'No losango, as diagonais se cruzam perpendicularmente.', 1),
(24, 7, 'Fácil', 'Qual é 10% de 300?', '3', '30', '60', '300', 'B', '10% de 300 é 30.', 1),
(24, 7, 'Médio', 'Qual é 25% de 80?', '15', '20', '25', '30', 'B', '25% de 80 é 20.', 1),
(24, 7, 'Difícil', 'Um desconto de 20% sobre R$ 150 dá:', 'R$ 20', 'R$ 25', 'R$ 30', 'R$ 35', 'C', '20% de 150 = 30.', 1),
(25, 7, 'Fácil', 'Qual é a probabilidade de sair cara em uma moeda?', '1/2', '1/3', '1/4', '2/3', 'A', 'Em uma moeda, há 2 resultados possíveis e um é cara.', 1),
(25, 7, 'Médio', 'Se um dado comum é lançado, qual é a probabilidade de sair 6?', '1/2', '1/3', '1/6', '1/8', 'C', 'Há seis faces e somente uma delas é 6.', 1),
(25, 7, 'Difícil', 'Qual é a probabilidade de sair um número par em um dado?', '1/2', '1/3', '2/3', '1/6', 'A', 'As faces pares são 2, 4 e 6, totalizando 3 de 6 = 1/2.', 1),
(26, 8, 'Fácil', 'Qual é o valor de 2³?', '4', '6', '8', '9', 'C', '2³ = 2 × 2 × 2 = 8.', 1),
(26, 8, 'Médio', 'Qual é o resultado de 5²?', '10', '20', '25', '30', 'C', '5² = 25.', 1),
(26, 8, 'Difícil', 'Qual é o valor de 3⁴?', '12', '27', '54', '81', 'D', '3⁴ = 3 × 3 × 3 × 3 = 81.', 1),
(27, 8, 'Fácil', '√25 é igual a:', '3', '4', '5', '6', 'C', '5 × 5 = 25.', 1),
(27, 8, 'Médio', 'Qual é o valor de √81?', '7', '8', '9', '10', 'C', '9 × 9 = 81.', 1),
(27, 8, 'Difícil', 'Qual é √144?', '10', '11', '12', '13', 'C', '12 × 12 = 144.', 1),
(28, 8, 'Fácil', 'Qual é a forma de 1.000.000 em notação científica?', '10³', '10⁴', '10⁵', '10⁶', 'D', '1.000.000 = 10⁶.', 1),
(28, 8, 'Médio', 'Qual é 3,2 × 10³ em decimal?', '32', '320', '3200', '32000', 'C', '3,2 × 10³ = 3200.', 1),
(28, 8, 'Difícil', 'Qual é 5,4 × 10⁻² em decimal?', '0,054', '0,54', '54', '540', 'A', '10⁻² = 0,01, então 5,4 × 0,01 = 0,054.', 1),
(29, 8, 'Fácil', '(a + b)² = ?', 'a² + 2ab + b²', 'a² + b²', 'a² + ab + b²', '2a + 2b', 'A', 'A identidade do quadrado da soma.', 1),
(29, 8, 'Médio', '(x - y)² = ?', 'x² - y²', 'x² - 2xy + y²', 'x² + 2xy + y²', 'x² - x y', 'B', 'O quadrado da diferença é x² - 2xy + y².', 1),
(29, 8, 'Difícil', '(x + 3)(x - 3) = ?', 'x² + 6x - 9', 'x² - 9', 'x² + 9', 'x² - 6x + 9', 'B', 'Produto da soma pela diferença resulta em diferença de quadrados.', 1),
(30, 8, 'Fácil', 'Qual é a fatoração de x² - 9?', '(x - 3)(x + 3)', '(x - 9)(x + 1)', '(x - 3)²', '(x + 9)²', 'A', 'x² - 9 é diferença de quadrados.', 1),
(30, 8, 'Médio', 'Qual é a fatoração de x² + 6x + 9?', '(x + 3)²', '(x - 3)²', '(x + 9)(x - 1)', '(x + 3)(x + 2)', 'A', 'O trinômio é um quadrado perfeito.', 1),
(30, 8, 'Difícil', 'Qual é a fatoração de x² - 5x + 6?', '(x - 2)(x - 3)', '(x - 1)(x - 6)', '(x + 2)(x + 3)', '(x - 2)(x + 3)', 'A', 'Os fatores devem somar -5 e multiplicar 6.', 1),
(31, 8, 'Fácil', 'Qual é o valor de x em x + 5 = 12?', '5', '6', '7', '8', 'C', 'x = 12 - 5 = 7.', 1),
(31, 8, 'Médio', 'Quanto vale x em 2x = 18?', '7', '8', '9', '10', 'C', 'x = 18 ÷ 2 = 9.', 1),
(31, 8, 'Difícil', 'Qual é o valor de x em 3x - 4 = 11?', '3', '4', '5', '6', 'C', '3x = 15, então x = 5.', 1),
(32, 8, 'Fácil', 'Qual é a solução do sistema x + y = 5 e x - y = 1?', 'x = 2, y = 3', 'x = 3, y = 2', 'x = 4, y = 1', 'x = 1, y = 4', 'B', 'Somando as equações resulta em 2x = 6, então x = 3 e y = 2.', 1),
(32, 8, 'Médio', 'Qual é o valor de y no sistema x + y = 10 e x = 4?', '4', '5', '6', '7', 'C', 'Se x = 4, então 4 + y = 10 e y = 6.', 1),
(32, 8, 'Difícil', 'Qual é a solução do sistema 2x + y = 7 e x - y = 2?', 'x = 3, y = 1', 'x = 2, y = 3', 'x = 4, y = 2', 'x = 5, y = 3', 'A', 'Somando as equações: 3x = 9, x = 3 e y = 1.', 1),
(33, 8, 'Fácil', 'Qual é o valor de f(2) na função f(x) = x + 3?', '4', '5', '6', '7', 'C', 'f(2) = 2 + 3 = 5.', 1),
(33, 8, 'Médio', 'Se f(x) = 2x + 1, qual é f(3)?', '5', '6', '7', '8', 'C', '2 × 3 + 1 = 7.', 1),
(33, 8, 'Difícil', 'Se f(x) = x² - 1, qual é f(4)?', '14', '15', '16', '17', 'C', '4² - 1 = 16 - 1 = 15.', 1),
(34, 8, 'Fácil', 'Qual é o valor da hipotenusa em um triângulo retângulo com catetos 3 e 4?', '4', '5', '6', '7', 'B', '3² + 4² = 25, então a hipotenusa vale 5.', 1),
(34, 8, 'Médio', 'Qual é o valor de c em c² = 5² + 12²?', '13', '15', '17', '19', 'A', '25 + 144 = 169 e √169 = 13.', 1),
(34, 8, 'Difícil', 'Um triângulo retângulo tem catetos 6 e 8. Qual é a hipotenusa?', '9', '10', '12', '14', 'B', '6² + 8² = 36 + 64 = 100, e √100 = 10.', 1),
(35, 8, 'Fácil', 'Qual é a área do retângulo com lados 6 e 3?', '9', '12', '18', '24', 'C', '6 × 3 = 18.', 1),
(35, 8, 'Médio', 'Qual é o volume de um cubo de lado 3?', '9', '18', '27', '36', 'C', '3³ = 27.', 1),
(35, 8, 'Difícil', 'Qual é o volume de um paralelepípedo 4 × 2 × 5?', '20', '30', '40', '50', 'C', '4 × 2 × 5 = 40.', 1),
(36, 9, 'Fácil', 'Qual é a raiz quadrada de 49?', '6', '7', '8', '9', 'B', '7 × 7 = 49.', 1),
(36, 9, 'Médio', 'Qual é o resultado de 2⁵?', '10', '16', '20', '32', 'D', '2⁵ = 32.', 1),
(36, 9, 'Difícil', 'Qual é o valor de 3⁻²?', '1/9', '1/3', '9', '3', 'A', '3⁻² = 1/3² = 1/9.', 1),
(37, 9, 'Fácil', 'Qual é a solução da equação x² - 9 = 0?', 'x = 3', 'x = -3', 'x = ±3', 'x = 9', 'C', 'x² = 9, então x = ±3.', 1),
(37, 9, 'Médio', 'O discriminante de x² - 5x + 6 = 0 é:', '1', '5', '13', '25', 'D', 'Δ = b² - 4ac = 25 - 24 = 1.', 1),
(37, 9, 'Difícil', 'As raízes de x² - 7x + 10 = 0 são:', '2 e 5', '1 e 10', '3 e 4', '2 e 3', 'A', 'Os números que somam 7 e multiplicam 10 são 2 e 5.', 1),
(38, 9, 'Fácil', 'Qual é a solução do sistema x + y = 5 e x - y = 1?', 'x = 3, y = 2', 'x = 2, y = 3', 'x = 4, y = 1', 'x = 1, y = 4', 'A', 'Adicionando as equações: 2x = 6, x = 3 e y = 2.', 1),
(38, 9, 'Médio', 'Se f(x) = 2x + 1, qual é f(4)?', '5', '7', '8', '9', 'D', '2 × 4 + 1 = 9.', 1),
(38, 9, 'Difícil', 'Qual é o valor de x no sistema 3x + y = 10 e x - y = 2?', 'x = 1, y = 2', 'x = 2, y = 4', 'x = 3, y = 1', 'x = 4, y = 2', 'C', 'Somando as equações: 4x = 12, x = 3 e y = 1.', 1),
(39, 9, 'Fácil', 'Qual é a equação da reta com coeficiente angular 2 e intercepto 3?', 'y = 2x + 3', 'y = 3x + 2', 'y = x + 2', 'y = 2x - 3', 'A', 'A forma de uma função afim é y = ax + b.', 1),
(39, 9, 'Médio', 'Se y = 3x - 2, qual é o valor de y quando x = 4?', '8', '10', '12', '14', 'C', '3 × 4 - 2 = 10.', 1),
(39, 9, 'Difícil', 'Qual é o ponto de intersecção da função y = 2x + 1 com o eixo y?', '(0,1)', '(1,0)', '(0,2)', '(2,0)', 'A', 'No eixo y, x = 0, então y = 1.', 1),
(40, 9, 'Fácil', 'Qual é o gráfico de uma função quadrática?', 'Linha reta', 'Parábola', 'Círculo', 'Elipse', 'B', 'A função quadrática tem gráfico em forma de parábola.', 1),
(40, 9, 'Médio', 'Qual é o vértice da parábola y = x² - 4?', '(0,-4)', '(0,4)', '(2,0)', '(-2,0)', 'A', 'A parábola y = x² - 4 tem vértice em (0, -4).', 1),
(40, 9, 'Difícil', 'A parábola y = x² - 6x + 9 tem vértice em:', '(2,1)', '(3,0)', '(3,1)', '(1,3)', 'B', 'A expressão é (x - 3)², então o vértice é (3,0).', 1),
(41, 9, 'Fácil', 'Se duas figuras são semelhantes, suas medidas correspondentes são:', 'Diferentes', 'Iguais', 'Proporcionais', 'Opostas', 'C', 'Figuras semelhantes têm medidas correspondentes proporcionais.', 1),
(41, 9, 'Médio', 'Se dois triângulos são semelhantes, seus ângulos correspondentes são:', 'Complementares', 'Suplementares', 'Congruentes', 'Diferentes', 'C', 'Ângulos correspondentes de triângulos semelhantes são congruentes.', 1),
(41, 9, 'Difícil', 'Se uma figura foi ampliada 3 vezes, a área fica:', '3 vezes maior', '6 vezes maior', '9 vezes maior', '12 vezes maior', 'C', 'A área aumenta pelo quadrado da razão de ampliação.', 1),
(42, 9, 'Fácil', 'Qual é a área de um retângulo com lados 5 e 8?', '13', '26', '40', '80', 'C', 'Área = 5 × 8 = 40.', 1),
(42, 9, 'Médio', 'Qual é o volume de um prisma retangular 2 × 3 × 4?', '12', '18', '24', '36', 'C', '2 × 3 × 4 = 24.', 1),
(42, 9, 'Difícil', 'Qual é o volume de um cilindro de raio 3 e altura 5, em termos de π?', '15π', '30π', '45π', '60π', 'C', 'V = πr²h = π × 9 × 5 = 45π.', 1),
(43, 9, 'Fácil', 'Qual é a média dos números 4, 6 e 8?', '5', '6', '7', '8', 'B', 'Média = (4 + 6 + 8) ÷ 3 = 6.', 1),
(43, 9, 'Médio', 'Qual é a mediana de 1, 3, 5, 7, 9?', '5', '6', '7', '9', 'A', 'A mediana é o valor central da sequência.', 1),
(43, 9, 'Difícil', 'Qual é a moda de 2, 2, 3, 5, 5, 5, 8?', '2', '3', '5', '8', 'C', 'O número 5 aparece mais vezes.', 1),
(44, 6, 'Fácil', 'Qual é o resultado de 9 × 6?', '48', '52', '54', '58', 'C', '9 × 6 = 54.', 1),
(44, 6, 'Médio', 'Qual é o resultado de 14 + 16?', '28', '30', '32', '34', 'C', '14 + 16 = 30.', 1),
(44, 6, 'Difícil', 'Qual é o valor de 7 × 9 + 3?', '60', '63', '66', '69', 'C', '7 × 9 = 63 e 63 + 3 = 66.', 1),
(45, 6, 'Fácil', 'Qual é o resultado de 100 - 37?', '53', '63', '73', '83', 'C', '100 - 37 = 63.', 1),
(45, 6, 'Médio', 'Qual é a metade de 48?', '20', '22', '24', '26', 'C', '48 ÷ 2 = 24.', 1),
(45, 6, 'Difícil', 'Qual é o dobro de 37?', '64', '68', '72', '74', 'D', '37 × 2 = 74.', 1),
(46, 7, 'Fácil', 'Qual é o resultado de 8 × 7?', '54', '56', '64', '48', 'B', '8 × 7 = 56.', 1),
(46, 7, 'Médio', 'Qual é o valor de 3² + 4²?', '7', '12', '24', '25', 'D', '9 + 16 = 25.', 1),
(46, 7, 'Difícil', 'Qual é o valor de 2/3 + 1/6?', '1/2', '5/6', '1', '7/6', 'B', '2/3 = 4/6, então 4/6 + 1/6 = 5/6.', 1),
(47, 8, 'Fácil', 'Qual é 4²?', '8', '12', '16', '20', 'C', '4² = 16.', 1),
(47, 8, 'Médio', 'Qual é o valor de 2³ × 2²?', '10', '16', '24', '32', 'D', '2³ × 2² = 8 × 4 = 32.', 1),
(47, 8, 'Difícil', 'Qual é o valor de (2²)³?', '8', '16', '32', '64', 'D', '(2²)³ = 4³ = 64.', 1),
(48, 9, 'Fácil', 'Qual é o valor de √16?', '2', '3', '4', '5', 'C', '4 × 4 = 16.', 1),
(48, 9, 'Médio', 'Qual é o resultado de 5² - 3²?', '8', '16', '25', '34', 'C', '25 - 9 = 16.', 1),
(48, 9, 'Difícil', 'Qual é a solução de 2x² - 8 = 0?', 'x = ±2', 'x = ±4', 'x = ±8', 'x = ±16', 'A', '2x² = 8 => x² = 4 => x = ±2.', 1);

INSERT INTO questoes (materia_id, ano_escolar, dificuldade, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, ativo)
SELECT m.id, 6, 'Fácil', 'Qual é o resultado de 7 + 9?', '14', '15', '16', '17', 'C', '7 + 9 = 16.', 1
FROM materias m WHERE m.nome = 'Operações básicas' LIMIT 1;

INSERT INTO questoes (materia_id, ano_escolar, dificuldade, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, ativo)
SELECT m.id, 6, 'Médio', 'Qual é o valor de 12 ÷ 3 + 4?', '8', '9', '10', '11', 'B', '12 ÷ 3 = 4 e 4 + 4 = 8.', 1
FROM materias m WHERE m.nome = 'Operações básicas' LIMIT 1;

INSERT INTO questoes (materia_id, ano_escolar, dificuldade, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, ativo)
SELECT m.id, 6, 'Difícil', 'Qual é o valor de 15 - 3 × 4?', '3', '6', '9', '12', 'A', 'Primeiro a multiplicação: 3 × 4 = 12; depois 15 - 12 = 3.', 1
FROM materias m WHERE m.nome = 'Operações básicas' LIMIT 1;

INSERT INTO questoes (materia_id, ano_escolar, dificuldade, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, ativo)
SELECT m.id, 6, 'Fácil', 'Qual é o valor de 1/4 + 1/4?', '1/2', '1/3', '2/4', '3/4', 'A', 'Metade mais metade resulta em 1/2.', 1
FROM materias m WHERE m.nome = 'Frações' LIMIT 1;

INSERT INTO questoes (materia_id, ano_escolar, dificuldade, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, ativo)
SELECT m.id, 6, 'Médio', 'Qual é o resultado de 3/5 + 1/5?', '4/5', '2/5', '3/10', '1', 'A', 'Como os denominadores são iguais, somamos os numeradores.', 1
FROM materias m WHERE m.nome = 'Frações' LIMIT 1;

INSERT INTO questoes (materia_id, ano_escolar, dificuldade, enunciado, alternativa_a, alternativa_c, alternativa_d, alternativa_b, resposta_correta, explicacao, ativo)
SELECT m.id, 6, 'Difícil', 'Qual é o valor de 2/3 × 3/4?', '1/2', '2/3', '3/4', '1/4', 'A', '2/3 × 3/4 = 6/12 = 1/2.', 1
FROM materias m WHERE m.nome = 'Frações' LIMIT 1;


SELECT 'Banco MathPlay inicializado com sucesso.' AS status;
