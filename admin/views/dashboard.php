<div class="wrap emma-ia-admin-wrap">
    <h1>
        <?php esc_html_e('Conversaciones de Emma IA', 'emma-ia'); ?>
    </h1>

    <?php
global $wpdb;
$table_conversations = $wpdb->prefix . 'emma_conversations';
$table_messages = $wpdb->prefix . 'emma_messages';

// Simple routing within the dashboard
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$conversation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($action === 'view' && $conversation_id > 0) {
    // View Single Conversation details
    $conversation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_conversations WHERE id = %d", $conversation_id));

    if (!$conversation) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Conversación no encontrada.', 'emma-ia') . '</p></div>';
    }
    else {
        $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_messages WHERE conversation_id = %d ORDER BY created_at ASC", $conversation_id));
?>

    <a href="?page=emma-ia-dashboard" class="button mb-2">&larr;
        <?php esc_html_e('Volver a la lista', 'emma-ia'); ?>
    </a>

    <div class="emma-conversation-details"
        style="background:#fff; padding:20px; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04); margin-top:15px;">
        <h2>
            <?php printf(esc_html__('Conversación #%d', 'emma-ia'), $conversation->id); ?>
        </h2>
        <p><strong>
                <?php esc_html_e('Fecha:', 'emma-ia'); ?>
            </strong>
            <?php echo esc_html($conversation->created_at); ?>
        </p>
        <p><strong>
                <?php esc_html_e('Session ID:', 'emma-ia'); ?>
            </strong>
            <?php echo esc_html($conversation->session_id); ?>
        </p>

        <?php if (!empty($conversation->summary)): ?>
        <div style="background:#f0f0f1; padding:15px; margin:20px 0; border-left:4px solid #2271b1;">
            <h4 style="margin-top:0;">
                <?php esc_html_e('Resumen Generado por IA:', 'emma-ia'); ?>
            </h4>
            <p style="margin-bottom:0; font-style: italic;">
                <?php echo esc_html($conversation->summary); ?>
            </p>
        </div>
        <?php
        endif; ?>

        <hr />
        <h3>
            <?php esc_html_e('Historial de Mensajes', 'emma-ia'); ?>
        </h3>
        <div class="emma-chat-history"
            style="max-height: 500px; overflow-y: auto; padding: 10px; background: #f9f9f9; border: 1px solid #e2e4e7;">
            <?php if ($messages): ?>
            <?php foreach ($messages as $msg): ?>
            <div
                style="margin-bottom:15px; padding: 10px; border-radius: 5px; <?php echo $msg->sender === 'user' ? 'background: #eef; margin-right:50px;' : 'background: #eefee8; margin-left:50px;'; ?>">
                <strong>
                    <?php echo esc_html(ucfirst($msg->sender)); ?>:
                </strong>
                <span style="color:#888; font-size:11px; float:right;">
                    <?php echo esc_html($msg->created_at); ?>
                </span>
                <div style="margin-top:5px; white-space: pre-wrap;">
                    <?php echo esc_html($msg->content); ?>
                </div>
            </div>
            <?php
            endforeach; ?>
            <?php
        else: ?>
            <p>
                <?php esc_html_e('No hay mensajes en esta conversación.', 'emma-ia'); ?>
            </p>
            <?php
        endif; ?>
        </div>
    </div>

    <?php
    }

}
else {
    // List Conversations
    $conversations = $wpdb->get_results("SELECT * FROM $table_conversations ORDER BY updated_at DESC LIMIT 50");
?>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-id" style="width: 50px;">ID</th>
                <th scope="col" class="manage-column column-date" style="width: 150px;">Fecha</th>
                <th scope="col" class="manage-column column-summary">Resumen</th>
                <th scope="col" class="manage-column column-actions" style="width: 100px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($conversations): ?>
            <?php foreach ($conversations as $conv): ?>
            <tr>
                <td>
                    <?php echo esc_html($conv->id); ?>
                </td>
                <td>
                    <?php echo esc_html($conv->updated_at); ?>
                </td>
                <td>
                    <?php echo esc_html($conv->summary ? $conv->summary : 'Sin resumen aún...'); ?>
                </td>
                <td>
                    <a href="?page=emma-ia-dashboard&action=view&id=<?php echo esc_attr($conv->id); ?>"
                        class="button button-small">
                        <?php esc_html_e('Ver Chat', 'emma-ia'); ?>
                    </a>
                </td>
            </tr>
            <?php
        endforeach; ?>
            <?php
    else: ?>
            <tr>
                <td colspan="4">
                    <?php esc_html_e('No hay conversaciones registradas aún.', 'emma-ia'); ?>
                </td>
            </tr>
            <?php
    endif; ?>
        </tbody>
    </table>
    <?php
}
?>
</div>