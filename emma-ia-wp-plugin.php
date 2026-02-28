<?php
/**
 * Plugin Name: Emma IA Chat
 * Plugin URI:  https://github.com/luismatos/emma-ia-wp-plugin
 * Description: Plugin de chat con Inteligencia Artificial utilizando la API de Asistentes de OpenAI.
 * Version:     1.0.0
 * Author:      Luis Matos (AI Assistant)
 * Text Domain: emma-ia
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('EMMA_IA_VERSION', '1.0.0');
define('EMMA_IA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EMMA_IA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary classes
require_once EMMA_IA_PLUGIN_DIR . 'includes/class-emma-db.php';
require_once EMMA_IA_PLUGIN_DIR . 'includes/class-emma-admin.php';
require_once EMMA_IA_PLUGIN_DIR . 'includes/class-emma-api.php';
require_once EMMA_IA_PLUGIN_DIR . 'includes/class-emma-frontend.php';

// Plugin Activation Hook
register_activation_hook(__FILE__, array('Emma_IA_DB', 'create_tables'));

// Initialize Admin
if (is_admin()) {
    new Emma_IA_Admin();
}

// Initialize Frontend
if (!is_admin()) {
    new Emma_IA_Frontend();
}
new Emma_IA_API();