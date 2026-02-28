<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Emma_IA_Frontend {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_chat_widget' ) );
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'emma-ia-chat-css', EMMA_IA_PLUGIN_URL . 'assets/css/emma-chat.css', array(), EMMA_IA_VERSION );
		wp_enqueue_script( 'emma-ia-chat-js', EMMA_IA_PLUGIN_URL . 'assets/js/emma-chat.js', array(), EMMA_IA_VERSION, true );
		
		// Pass plugin URL and standard WP AJAX URL to JS
		wp_localize_script( 'emma-ia-chat-js', 'emma_ia_globals', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'botName' => get_option( 'emma_ia_bot_name', 'Emma' ),
			'botAvatar' => get_option( 'emma_ia_bot_avatar', EMMA_IA_PLUGIN_URL . 'assets/img/default-avatar.png' ),
			'nonce' => wp_create_nonce('emma_ia_nonce'),
		) );
	}

	public function render_chat_widget() {
		$bot_name = get_option( 'emma_ia_bot_name', 'Emma' );
		$bot_avatar = get_option( 'emma_ia_bot_avatar' );
		
		// Fallback avatar if empty
		if ( empty( $bot_avatar ) ) {
			// Using an inline SVG or a default URL here
			$bot_avatar = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%232271b1"/><text x="50" y="65" font-family="Arial" font-size="40" fill="white" text-anchor="middle">' . esc_attr( substr( $bot_name, 0, 1 ) ) . '</text></svg>';
		}
		
		?>
		<div id="emma-ia-widget-container" class="emma-ia-widget-container">
			<!-- Chat Bubble -->
			<div id="emma-ia-bubble" class="emma-ia-bubble">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
			</div>

			<!-- Chat Window -->
			<div id="emma-ia-chat-window" class="emma-ia-chat-window emma-ia-hidden">
				<div class="emma-ia-chat-header">
					<div class="emma-ia-header-info">
						<img src="<?php echo esc_url( $bot_avatar ); ?>" alt="Avatar" class="emma-ia-avatar">
						<div class="emma-ia-title">
							<h4><?php echo esc_html( $bot_name ); ?></h4>
							<span class="emma-ia-status-dot"></span> <span class="emma-ia-status-text">En línea</span>
						</div>
					</div>
					<button id="emma-ia-close-btn" class="emma-ia-close-btn">&times;</button>
				</div>
				
				<div id="emma-ia-chat-messages" class="emma-ia-chat-messages">
					<!-- Welcome message -->
					<div class="emma-ia-message-wrap bot-wrap">
						<div class="emma-ia-message bot">
							¡Hola! Soy <?php echo esc_html( $bot_name ); ?>. ¿En qué te puedo ayudar hoy?
						</div>
					</div>
				</div>

				<div class="emma-ia-chat-input-area">
					<form id="emma-ia-chat-form">
						<input type="text" id="emma-ia-input" autocomplete="off" placeholder="Escribe tu mensaje..." required>
						<button type="submit" id="emma-ia-send-btn">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
						</button>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
