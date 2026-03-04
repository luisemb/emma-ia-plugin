<?php
if (!defined('ABSPATH')) {
    exit;
}

class Emma_IA_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('Emma IA', 'emma-ia'),
            __('Emma IA', 'emma-ia'),
            'manage_options',
            'emma-ia-settings',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'emma-ia-settings',
            __('Configuración', 'emma-ia'),
            __('Configuración', 'emma-ia'),
            'manage_options',
            'emma-ia-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'emma-ia-settings',
            __('Conversaciones', 'emma-ia'),
            __('Conversaciones', 'emma-ia'),
            'manage_options',
            'emma-ia-dashboard',
            array($this, 'render_dashboard_page')
        );
    }

    public function enqueue_admin_assets($hook)
    {
        // Only enqueue on our plugin pages
        if (strpos($hook, 'emma-ia') === false) {
            return;
        }

        // We will create this CSS file later
        wp_enqueue_style('emma-ia-admin-css', EMMA_IA_PLUGIN_URL . 'assets/css/emma-admin.css', array(), EMMA_IA_VERSION);
    }

    public function register_settings()
    {
        register_setting('emma_ia_settings_group', 'emma_ia_openai_api_key');
        register_setting('emma_ia_settings_group', 'emma_ia_assistant_id');
        register_setting('emma_ia_settings_group', 'emma_ia_system_prompt');
        register_setting('emma_ia_settings_group', 'emma_ia_bot_name', array('default' => 'Emma'));
        register_setting('emma_ia_settings_group', 'emma_ia_bot_avatar');
    }

    public function render_settings_page()
    {
        include EMMA_IA_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public function render_dashboard_page()
    {
        include EMMA_IA_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}