-- Corrige questões da carga inicial que ficaram associadas à matéria errada.
USE mathplay;

UPDATE questoes q SET q.materia_id = (SELECT MIN(m.id) FROM materias m WHERE m.nome = 'Números inteiros' AND m.ano_escolar = 7 AND m.ativo = 1) WHERE q.id IN (45, 46, 47) AND q.ano_escolar = 7;
UPDATE questoes q SET q.materia_id = (SELECT MIN(m.id) FROM materias m WHERE m.nome = 'Potenciação' AND m.ano_escolar = 8 AND m.ativo = 1) WHERE q.id IN (69, 70, 71) AND q.ano_escolar = 8;
UPDATE questoes q SET q.materia_id = (SELECT MIN(m.id) FROM materias m WHERE m.nome = 'Radiciação' AND m.ano_escolar = 9 AND m.ativo = 1) WHERE q.id IN (99, 135) AND q.ano_escolar = 9;
UPDATE questoes q SET q.materia_id = (SELECT MIN(m.id) FROM materias m WHERE m.nome = 'Potenciação' AND m.ano_escolar = 9 AND m.ativo = 1) WHERE q.id IN (100, 101, 136) AND q.ano_escolar = 9;
UPDATE questoes q SET q.materia_id = (SELECT MIN(m.id) FROM materias m WHERE m.nome = 'Equação do 2º grau' AND m.ano_escolar = 9 AND m.ativo = 1) WHERE q.id IN (102, 103, 104, 137) AND q.ano_escolar = 9;
UPDATE questoes q SET q.materia_id = (SELECT MIN(m.id) FROM materias m WHERE m.nome = 'Operações básicas' AND m.ano_escolar = 6 AND m.ativo = 1) WHERE q.id BETWEEN 123 AND 128 AND q.ano_escolar = 6;