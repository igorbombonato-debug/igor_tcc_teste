<?php

// Exemplo de configuração do envio de e-mails; preencha as credenciais no arquivo local de configuração.
return [
    // Define qual bloco abaixo será usado como provedor padrão.
    'provider' => 'gmail',
    'providers' => [
        'gmail' => [
            // Informe usuário, senha de aplicativo e endereço/nome que aparecerão como remetente.
            'username' => '',
            'password' => '',
            'from_address' => '',
            'from_name' => 'Mathematics Education',
        ],
        'outlook' => [
            // O segundo bloco permite configurar a mesma aplicação para uma conta Outlook.
            'username' => '',
            'password' => '',
            'from_address' => '',
            'from_name' => 'Mathematics Education',
        ],
    ],
    // URL base usada para montar links da aplicação, como os de recuperação de senha.
    'base_url' => 'http://localhost/igor_tcc_teste',
];
