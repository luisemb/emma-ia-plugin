<?php
if (!defined('ABSPATH')) {
    exit;
}

class Emma_IA_API
{

    public function __construct()
    {
        // AJAX hooks
        add_action('wp_ajax_emma_ia_send_message', array($this, 'handle_incoming_message'));
        add_action('wp_ajax_nopriv_emma_ia_send_message', array($this, 'handle_incoming_message'));
    }

    public function handle_incoming_message()
    {
        check_ajax_referer('emma_ia_nonce', '_ajax_nonce');

        $message = isset($_POST['message']) ? sanitize_text_field(wp_unslash($_POST['message'])) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';

        if (empty($message) || empty($session_id)) {
            wp_send_json_error('Datos incompletos.');
        }

        // Security: Max character limit (e.g. 500 characters)
        if (mb_strlen($message, 'UTF-8') > 500) {
            wp_send_json_error('El mensaje es demasiado largo. Máximo 500 caracteres admitidos.');
        }

        // Security: Rate Limiting based on IP
        if ($this->is_rate_limited()) {
            wp_send_json_error('Has enviado demasiados mensajes muy rápido. Por favor, espera un minuto o más antes de continuar.');
        }

        $is_logged_in = is_user_logged_in();
        $api_key = get_option('emma_ia_openai_api_key');

        $assistant_id = get_option('emma_ia_assistant_id');
        $system_prompt = get_option('emma_ia_system_prompt');

        if (empty($api_key)) {
            wp_send_json_error('La API Key de OpenAI no está configurada.');
        }

        // Security: Daily Quota Limit
        $quota_check = $this->check_daily_quota($is_logged_in);
        if (is_wp_error($quota_check)) {
            wp_send_json_error($quota_check->get_error_message());
        }

        // 1. Save User Message and get/create conversation ID
        $conversation_id = $this->get_or_create_conversation($session_id);
        $this->save_message($conversation_id, 'user', $message);

        // 2. Determine AI strategy. If Assistant ID exists, use Assistants API. 
        // If not, use standard Chat Completions for generic fallback.
        $reply = '';
        if (!empty($assistant_id)) {
            $reply = $this->call_openai_assistant($api_key, $assistant_id, $message, $session_id, $is_logged_in);
        }
        else {
            $reply = $this->call_openai_chat($api_key, $message, $system_prompt, $is_logged_in);
        }

        if (is_wp_error($reply)) {
            wp_send_json_error($reply->get_error_message());
        }

        // 3. Save AI message
        $this->save_message($conversation_id, 'bot', $reply);

        // 4. Check if we should summarize
        $this->maybe_summarize_conversation($conversation_id, $session_id);

        wp_send_json_success(array(
            'reply' => $reply,
        ));
    }

    private function is_rate_limited()
    {
        // Limit to 10 requests per minute per IP
        $ip_address = $this->get_client_ip();
        $transient_key = 'emma_rate_' . md5($ip_address);

        $requests = get_transient($transient_key);

        if (false === $requests) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return false; // Not limited
        }

        if ($requests >= 10) {
            return true; // Limited
        }

        set_transient($transient_key, $requests + 1, MINUTE_IN_SECONDS);
        return false;
    }

    private function check_daily_quota($is_logged_in)
    {
        if ($is_logged_in) {
            $user_id = get_current_user_id();
            $transient_key = 'emma_quota_user_' . $user_id;
            $limit = (int)get_option('emma_ia_user_daily_quota', 100);
        }
        else {
            $ip_address = $this->get_client_ip();
            $transient_key = 'emma_quota_ip_' . md5($ip_address);
            $limit = (int)get_option('emma_ia_visitor_daily_quota', 20);
        }

        $requests_today = get_transient($transient_key);

        if (false === $requests_today) {
            set_transient($transient_key, 1, DAY_IN_SECONDS);
            return true;
        }

        if ((int)$requests_today >= $limit) {
            return new WP_Error('quota_exceeded', 'Has alcanzado tu límite de mensajes por hoy. Vuelve mañana.');
        }

        set_transient($transient_key, (int)$requests_today + 1, DAY_IN_SECONDS);
        return true;
    }

    private function get_client_ip()
    {
        $ip = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        }
        elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        return $ip;
    }

    private function get_or_create_conversation($session_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'emma_conversations';

        $row = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE session_id = %s", $session_id));

        if ($row) {
            return $row->id;
        }

        $wpdb->insert(
            $table_name,
            array(
            'session_id' => $session_id,
            'user_id' => get_current_user_id()
        )
        );

        return $wpdb->insert_id;
    }

    private function save_message($conversation_id, $sender, $content)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'emma_messages';

        $wpdb->insert(
            $table_name,
            array(
            'conversation_id' => $conversation_id,
            'sender' => $sender,
            'content' => $content,
        )
        );
    }

    private function call_openai_chat($api_key, $message, $system_prompt = '', $is_logged_in = false)
    {
        // Fallback simple chat completion
        $url = 'https://api.openai.com/v1/chat/completions';
        $bot_name = get_option('emma_ia_bot_name', 'Emma');

        // Check if there is a custom prompt, otherwise use a generic one
        $default_prompt = "Eres un asistente virtual llamado $bot_name, muy útil y amable.";
        $final_prompt = !empty($system_prompt) ? $system_prompt : $default_prompt;

        $auth_context = $is_logged_in ? "El usuario actual con el que hablas ESTÁ logueado/autenticado en el sistema." : "El usuario actual NO está logueado en el sistema (es un visitante anónimo).";
        $final_prompt .= "\n\n[CONTEXTO DEL SISTEMA: " . $auth_context . "]";

        $body = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                    array('role' => 'system', 'content' => $final_prompt),
                    array('role' => 'user', 'content' => $message)
            )
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body),
            'timeout' => 30, // Can take a bit
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        return new WP_Error('api_error', 'Invalid response from AI');
    }

    private function call_openai_assistant($api_key, $assistant_id, $message, $session_id, $is_logged_in = false)
    {
        // Simplified implementation of Assistants API
        // Real implementation requires Thread creation, Message addition, Run execution, and polling logic.
        // For the sake of this prompt and limitation of sync PHP execution, we will mock it or use 
        // a simplified curl/wp_remote loop, but Assistants API is async by nature. 
        // Let's implement the thread -> run -> poll flow.

        // 1. Thread handling. Ideally stored in DB. For simplicity, creating a new thread per session if not cached.
        $thread_id = get_transient('emma_thread_' . $session_id);

        if (!$thread_id) {
            $thread_response = wp_remote_post('https://api.openai.com/v1/threads', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'OpenAI-Beta' => 'assistants=v2'
                )
            ));

            if (is_wp_error($thread_response))
                return $thread_response;
            $thread_data = json_decode(wp_remote_retrieve_body($thread_response), true);
            if (!isset($thread_data['id']))
                return new WP_Error('thread_error', 'Error creando hilo.');

            $thread_id = $thread_data['id'];
            set_transient('emma_thread_' . $session_id, $thread_id, 12 * HOUR_IN_SECONDS);
        }

        // 2. Add message to thread
        wp_remote_post("https://api.openai.com/v1/threads/$thread_id/messages", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2'
            ),
            'body' => wp_json_encode(array('role' => 'user', 'content' => $message))
        ));

        // 3. Create Run
        $auth_context = $is_logged_in ? "El usuario actual ESTÁ logueado/autenticado en el sistema." : "El usuario actual NO está logueado en el sistema (es un visitante anónimo).";
        $run_response = wp_remote_post("https://api.openai.com/v1/threads/$thread_id/runs", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2'
            ),
            'body' => wp_json_encode(array(
                'assistant_id' => $assistant_id,
                'additional_instructions' => "[CONTEXTO DEL SISTEMA: " . $auth_context . "]"
            ))
        ));

        if (is_wp_error($run_response))
            return $run_response;
        $run_data = json_decode(wp_remote_retrieve_body($run_response), true);
        if (!isset($run_data['id'])) {
            // Probably rate limit or error with Assistant ID
            return new WP_Error('run_error', 'Fallo al iniciar el run. Verifica si el Assistant ID es válido. Detalle: ' . wp_remote_retrieve_body($run_response));
        }

        $run_id = $run_data['id'];

        // 4. Poll for completion (Max 30 seconds to avoid timeout)
        $attempts = 0;
        while ($attempts < 15) {
            sleep(2);
            $check_response = wp_remote_get("https://api.openai.com/v1/threads/$thread_id/runs/$run_id", array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'OpenAI-Beta' => 'assistants=v2'
                )
            ));

            $check_data = json_decode(wp_remote_retrieve_body($check_response), true);

            if ($check_data['status'] === 'completed') {
                break;
            }
            elseif (in_array($check_data['status'], array('failed', 'cancelled', 'expired'))) {
                return new WP_Error('run_failed', 'El run del asistente falló con estado: ' . $check_data['status']);
            }
            $attempts++;
        }

        // 5. Retrieve messages
        $msg_response = wp_remote_get("https://api.openai.com/v1/threads/$thread_id/messages", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'OpenAI-Beta' => 'assistants=v2'
            )
        ));

        $msg_data = json_decode(wp_remote_retrieve_body($msg_response), true);
        if (isset($msg_data['data'][0]['content'][0]['text']['value'])) {
            return $msg_data['data'][0]['content'][0]['text']['value'];
        }

        return new WP_Error('retrieve_error', 'No se pudo leer la respuesta final.');
    }

    private function maybe_summarize_conversation($conversation_id, $session_id)
    {
        // As a simple rule, we summazize if there are exactly 5 or 10 messages, or something similar.
        // For a robust system, we might use wp_schedule_single_event on a CRON 
        // if the session has been idle for 5 minutes.

        global $wpdb;
        $table_name = $wpdb->prefix . 'emma_messages';

        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE conversation_id = %d", $conversation_id));

        // Summarize when there are exactly 4 or 10 messages (for testing)
        if ($count == 4 || $count == 10) {
            // Trigger a background async summarization (mocked as sync here for simplicity)
            $this->generate_summary($conversation_id);
        }
    }

    private function generate_summary($conversation_id)
    {
        global $wpdb;
        $table_conv = $wpdb->prefix . 'emma_conversations';
        $table_msg = $wpdb->prefix . 'emma_messages';

        $messages = $wpdb->get_results($wpdb->prepare("SELECT sender, content FROM $table_msg WHERE conversation_id = %d ORDER BY created_at ASC", $conversation_id));

        if (empty($messages))
            return;

        $text_history = "";
        foreach ($messages as $m) {
            $text_history .= strtoupper($m->sender) . ": " . $m->content . "\n";
        }

        $api_key = get_option('emma_ia_openai_api_key');
        if (empty($api_key))
            return;

        $url = 'https://api.openai.com/v1/chat/completions';
        $body = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                    array('role' => 'system', 'content' => "Resume la siguiente conversación en un párrafo muy corto (máximo 2 oraciones), para que el administrador sepa de qué se trató. Habla en tercera persona."),
                    array('role' => 'user', 'content' => $text_history)
            )
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body)
        ));

        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($data['choices'][0]['message']['content'])) {
                $summary = $data['choices'][0]['message']['content'];
                // Update db
                $wpdb->update(
                    $table_conv,
                    array('summary' => $summary),
                    array('id' => $conversation_id)
                );
            }
        }
    }
}