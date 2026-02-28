<?php
if (!defined('ABSPATH')) {
    exit;
}

class Emma_IA_DB
{

    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_conversations = $wpdb->prefix . 'emma_conversations';
        $table_messages = $wpdb->prefix . 'emma_messages';

        $sql_conversations = "CREATE TABLE $table_conversations (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) DEFAULT 0,
			session_id varchar(255) NOT NULL,
			summary text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY session_id (session_id)
		) $charset_collate;";

        $sql_messages = "CREATE TABLE $table_messages (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) NOT NULL,
			sender varchar(50) NOT NULL, /* 'user' or 'bot' */
			content text NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_id (conversation_id)
		) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_conversations);
        dbDelta($sql_messages);

        // Update DB version option
        update_option('emma_ia_db_version', '1.0');
    }
}