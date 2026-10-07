# Mathematics Education

Sistema web educacional para aprendizagem de matemática para alunos do 6º ao 9º ano. O nome exibido da aplicação é **Mathematics Education**. O projeto foi desenvolvido para rodar em ambientes locais como XAMPP/Laragon com PHP e MySQL.

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

## Como navegar pelo código

Os comentários em português explicam a responsabilidade dos arquivos, os fluxos principais e as decisões que podem não ser óbvias. Use este mapa para seguir uma funcionalidade:

- **Entrada e permissões:** `public/login.php` inicia a autenticação; `includes/auth.php` contém as verificações de sessão e de perfil; `includes/navbar.php` monta a navegação conforme o tipo de usuário.
- **Componentes compartilhados:** `includes/header.php` e `includes/footer.php` montam a estrutura comum das páginas; `includes/functions.php` reúne acesso a dados e funções reutilizáveis.
- **Experiência do aluno:** `public/dashboard.php`, `materias.php`, `desempenho.php`, `historico.php`, `ranking.php` e `perfil.php` apresentam áreas do estudante; `jogos/` contém a seleção e a lógica dos jogos.
- **Resultados dos jogos:** as páginas em `jogos/` usam endpoints em `api/` para buscar questões e registrar respostas, partidas e desempenho.
- **Acompanhamento docente:** `professor/index.php` resume o desempenho das turmas e `professor/aluno.php` detalha as partidas e respostas de um aluno.
- **Administração:** `admin/` contém as páginas de gerenciamento; `database/` define a estrutura inicial e as migrações incrementais.
- **Recuperação de senha:** `public/recuperar_senha.php` solicita o link, `includes/mailer.php` prepara o envio por SMTP e `public/redefinir_senha.php` valida o token de uso único.
- **Apresentação e acessibilidade:** `assets/css/` define os estilos, enquanto `assets/js/app.js` implementa os controles compartilhados da interface.
- **Configuração local:** `config/` concentra as conexões e exemplos de configuração. Arquivos locais com segredos não devem ser compartilhados ou versionados.

Os comentários descrevem a intenção e as etapas relevantes do código. Quando mudar um fluxo, mantenha os comentários correspondentes alinhados com o comportamento atualizado.

## Instalação local

1. Copie a pasta do projeto para a pasta de projetos do seu servidor local, por exemplo:
   - XAMPP: `C:\xampp\htdocs\mathplay`
   - Laragon: `C:\laragon\www\mathplay`
2. Inicie o Apache e o MySQL.
3. Acesse o phpMyAdmin ou o cliente MySQL e importe o arquivo `database/database.sql`.
4. Verifique se o banco chama `mathplay`.
5. Na pasta do projeto, instale as dependências executando `composer install`.
6. Copie `config/mail.example.php` para `config/mail.local.php` e configure os dados SMTP, conforme a seção abaixo.
7. Abra no navegador:
   - `http://localhost/mathplay/public/login.php`

Em instalações que já possuem o banco criado, importe uma vez `database/migration_professores.sql` antes de usar o cadastro de professores e o registro detalhado das tentativas da Memória. Importe também `database/migration_recuperacao_senhas.sql` para ativar a redefinição de senha.

## Configuração de e-mail para recuperação de senha

O envio usa PHPMailer por SMTP e aceita Gmail ou Outlook. Copie `config/mail.example.php` para `config/mail.local.php`, escolha `gmail` ou `outlook` em `provider` e informe o usuário, a senha de aplicativo e o endereço remetente na seção correspondente em `providers`. As duas contas podem ficar configuradas ao mesmo tempo; `provider` seleciona qual delas envia. Alternativamente, defina `MATHPLAY_MAIL_PROVIDER` como `gmail` ou `outlook` no ambiente do Apache para escolher sem editar o arquivo.

- Gmail usa `smtp.gmail.com`, porta 587 com TLS; Outlook usa `smtp-mail.outlook.com`, porta 587 com TLS.
- Configure `username`, `password` e `from_address` em cada conta que será usada.
- Ajuste `base_url` para o endereço público da aplicação, sem a barra final (por exemplo, `http://localhost/igor_tcc_teste`).

O arquivo `config/mail.local.php` é ignorado pelo Git para não publicar credenciais. Para Gmail, use uma senha de app, não a senha normal da conta. Sem uma configuração SMTP válida, a tela de recuperação informa que o envio não está disponível e não cria um link exposto na página.

## Primeiro acesso

Não existem credenciais padrão. Use a opção `Criar conta` na tela de login e escolha seu próprio usuário, e-mail e senha.

## Funcionalidades

- Login e cadastro de alunos e professores
- Recuperação de senha por link temporário, válido por uma hora e de uso único
- Painel do professor com acompanhamento dos jogos, desempenho e respostas de todos os alunos
- Controles de acessibilidade para aumentar, diminuir e restaurar o tamanho do texto, com preferência salva no navegador
- Painel do estudante com XP, nível e desempenho
- Ranking geral e por ano escolar
- Jogos: Queimada Matemática e Memória Matemática
- Gestão de questões, matérias e usuários pelo painel administrativo
- Registro de partidas, respostas e evolução do desempenho

## Observação

O projeto foi desenvolvido para execução local e usa PDO com preparado de statements para se conectar ao banco MySQL.

Se o MySQL estiver acessível em outra porta ou com senha personalizada, ajuste os valores em `config/database.php`.
