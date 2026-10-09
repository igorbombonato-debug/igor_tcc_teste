<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = get_logged_user();
if (!in_array($user['tipo'], ['aluno', 'professor'], true)) {
    http_response_code(403);
    exit('Este perfil não pode usar o chat.');
}

if (empty($_SESSION['chat_csrf_token'])) {
    $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Mensagens | Mathematics Education';
$chatConfig = json_encode([
    'usuarioId' => (int) $user['id'],
    'csrfToken' => $_SESSION['chat_csrf_token'],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section chat-page">
    <div class="chat-heading">
        <div>
            <span class="section-badge">Comunicação</span>
            <h1 class="mt-3 mb-0">Mensagens</h1>
        </div>
    </div>

    <div class="chat-layout">
        <aside class="chat-sidebar" aria-label="Conversas e contatos">
            <label class="visually-hidden" for="chatContactSearch">Buscar aluno ou professor</label>
            <div class="input-group chat-search">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input id="chatContactSearch" type="search" class="form-control" placeholder="Buscar aluno ou professor" autocomplete="off">
            </div>
            <div id="chatContactResults" class="chat-contact-results" role="listbox" aria-label="Resultados da busca" hidden></div>
            <h2 class="chat-list-heading">Conversas</h2>
            <div id="chatConversationList" class="chat-conversation-list" aria-live="polite"></div>
        </aside>

        <section class="chat-window" aria-label="Mensagens da conversa">
            <div id="chatEmptyState" class="chat-empty-state">
                <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                <p>Escolha uma conversa ou busque um contato.</p>
            </div>
            <div id="chatActiveWindow" class="chat-active-window" hidden>
                <header class="chat-conversation-header">
                    <div class="chat-contact-avatar" id="chatContactInitials" aria-hidden="true"></div>
                    <div>
                        <h2 id="chatContactName"></h2>
                        <span id="chatContactRole"></span>
                    </div>
                </header>
                <div id="chatMessages" class="chat-messages" role="log" aria-live="polite" aria-relevant="additions text"></div>
                <div id="chatStatus" class="chat-status" role="status" aria-live="polite"></div>
                <form id="chatMessageForm" class="chat-compose">
                    <label class="visually-hidden" for="chatMessageInput">Escrever mensagem</label>
                    <textarea id="chatMessageInput" class="form-control" rows="1" maxlength="2000" placeholder="Escreva uma mensagem..." required></textarea>
                    <button class="btn btn-primary chat-send-button" type="submit" aria-label="Enviar mensagem" title="Enviar mensagem">
                        <i class="bi bi-send-fill" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </section>
    </div>
</section>
<script id="chatConfig" type="application/json"><?php echo $chatConfig; ?></script>
<?php
$extraJs = '<script src="/igor_tcc_teste/assets/js/chat.js?v=2"></script>';
require_once __DIR__ . '/../includes/footer.php';
?>