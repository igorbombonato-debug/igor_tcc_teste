<?php

// Configura o envio SMTP e monta e-mails de recuperação de senha.
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function mathplay_mail_configuration(): array
{
    // Lê configurações locais opcionais; credenciais também podem vir do ambiente.
    $localConfigPath = __DIR__ . '/../config/mail.local.php';
    $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
    if (!is_array($localConfig)) {
        throw new RuntimeException('A configuração local de e-mail está inválida.');
    }

    $provider = strtolower(trim((string) (getenv('MATHPLAY_MAIL_PROVIDER') ?: ($localConfig['provider'] ?? ''))));
    $providerSettings = $localConfig['providers'][$provider] ?? null;
    $providerDefaults = [
        'gmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
        'outlook' => ['host' => 'smtp-mail.outlook.com', 'port' => 587, 'encryption' => 'tls'],
    ];
    // Aceita somente provedores configurados explicitamente nesta lista.
    if (!isset($providerDefaults[$provider]) || !is_array($providerSettings)) {
        throw new RuntimeException('Escolha gmail ou outlook e configure a conta em config/mail.local.php.');
    }

    $environmentPrefix = 'MATHPLAY_' . strtoupper($provider) . '_';
    $config = [
        ...$providerDefaults[$provider],
        'username' => trim((string) (getenv($environmentPrefix . 'USERNAME') ?: ($providerSettings['username'] ?? ''))),
        'password' => (string) (getenv($environmentPrefix . 'PASSWORD') ?: ($providerSettings['password'] ?? '')),
        'from_address' => trim((string) (getenv($environmentPrefix . 'FROM_ADDRESS') ?: ($providerSettings['from_address'] ?? ''))),
        'from_name' => trim((string) (getenv($environmentPrefix . 'FROM_NAME') ?: ($providerSettings['from_name'] ?? 'Mathematics Education'))),
        'base_url' => rtrim(trim((string) (getenv('MATHPLAY_BASE_URL') ?: ($localConfig['base_url'] ?? 'http://localhost/igor_tcc_teste'))), '/'),
    ];

    // Interrompe o envio se faltarem credenciais ou endereços válidos.
    if (
        $config['username'] === ''
        || $config['password'] === ''
        || !filter_var($config['from_address'], FILTER_VALIDATE_EMAIL)
        || !filter_var($config['base_url'], FILTER_VALIDATE_URL)
    ) {
        throw new RuntimeException('O envio de e-mail não está configurado. Confira os dados SMTP em config/mail.local.php.');
    }

    return $config;
}

function send_password_recovery_email(string $recipient, string $recipientName, string $recoveryUrl, array $config): void
{
    // Configura SMTP com criptografia e envia versões HTML e texto do mesmo aviso.
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->Port = $config['port'];
    $mail->SMTPSecure = $config['encryption'] === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->setFrom($config['from_address'], $config['from_name']);
    $mail->addAddress($recipient, $recipientName);
    $mail->isHTML(true);
    $mail->Subject = 'Redefinição de senha - Mathematics Education';
    // Escapa os dados variáveis antes de inseri-los no conteúdo HTML.
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeUrl = htmlspecialchars($recoveryUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mail->Body = '<p>Olá, ' . $safeName . '.</p>'
        . '<p>Recebemos uma solicitação para redefinir sua senha do Mathematics Education.</p>'
        . '<p><a href="' . $safeUrl . '">Criar uma nova senha</a></p>'
        . '<p>Este link expira em 1 hora e só pode ser usado uma vez. '
        . 'Se você não solicitou a redefinição, ignore esta mensagem.</p>';
    $mail->AltBody = "Olá, {$recipientName}.\n\n"
        . "Para criar uma nova senha do Mathematics Education, abra este link:\n{$recoveryUrl}\n\n"
        . 'O link expira em 1 hora e só pode ser usado uma vez. Se você não solicitou a redefinição, ignore esta mensagem.';

    try {
        $mail->send();
    } catch (PHPMailer\Exception $error) {
        error_log('Falha ao enviar e-mail de recuperação do Mathematics Education: ' . $mail->ErrorInfo);
        throw new RuntimeException('Não foi possível enviar o e-mail de recuperação. Verifique a configuração SMTP.', 0, $error);
    }
}
