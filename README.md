# MathPlay

Sistema web educacional para aprendizagem de matemática para alunos do 6º ao 9º ano, pensado para rodar em ambientes locais como XAMPP/Laragon com PHP e MySQL.

## Requisitos

- PHP 8.2+ ou 8.3
- MySQL 8+
- Apache ou Nginx
- XAMPP/Laragon/Servidor local com suporte a PHP e MySQL

## Estrutura principal

- `public/` — páginas públicas do aluno
- `admin/` — área administrativa
- `jogos/` — jogos matemáticos
- `api/` — endpoints utilizados pelos jogos
- `includes/` — autenticação, helpers e funções reutilizáveis
- `config/` — configuração do banco
- `database/` — script SQL inicial
- `assets/` — CSS, imagens e JavaScript

## Instalação local

1. Copie a pasta do projeto para a pasta de projetos do seu servidor local, por exemplo:
   - XAMPP: `C:\xampp\htdocs\mathplay`
   - Laragon: `C:\laragon\www\mathplay`
2. Inicie o Apache e o MySQL.
3. Acesse o phpMyAdmin ou o cliente MySQL e importe o arquivo `database/database.sql`.
4. Verifique se o banco chama `mathplay`.
5. Abra no navegador:
   - `http://localhost/mathplay/public/login.php`

## Primeiro acesso

Não existem credenciais padrão. Use a opção `Criar conta` na tela de login e escolha seu próprio usuário, e-mail e senha.

## Funcionalidades

- Login e cadastro de alunos
- Painel do estudante com XP, nível e desempenho
- Ranking geral e por ano escolar
- Jogos: Queimada Matemática e Memória Matemática
- Gestão de questões, matérias e usuários pelo painel administrativo
- Registro de partidas, respostas e evolução do desempenho

## Observação

O projeto foi desenvolvido para execução local e usa PDO com preparado de statements para se conectar ao banco MySQL.

Se o MySQL estiver acessível em outra porta ou com senha personalizada, ajuste os valores em `config/database.php`.
