<div class="wrap emma-ia-admin-wrap">
    <h1>
        <?php esc_html_e('Emma IA - Configuración', 'emma-ia'); ?>
    </h1>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields('emma_ia_settings_group'); ?>
        <?php do_settings_sections('emma_ia_settings_group'); ?>

        <table class="form-table">

            <tr valign="top">
                <th scope="row">
                    <?php esc_html_e('OpenAI API Key', 'emma-ia'); ?>
                </th>
                <td>
                    <input type="password" name="emma_ia_openai_api_key"
                        value="<?php echo esc_attr(get_option('emma_ia_openai_api_key')); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('Tu API Key secreta de OpenAI.', 'emma-ia'); ?>
                    </p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php esc_html_e('OpenAI Assistant ID', 'emma-ia'); ?>
                </th>
                <td>
                    <input type="text" name="emma_ia_assistant_id"
                        value="<?php echo esc_attr(get_option('emma_ia_assistant_id')); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('El ID del asistente que has configurado en OpenAI (ej. asst_abc123).', 'emma-ia'); ?>
                    </p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php esc_html_e('Prompt de Sistema', 'emma-ia'); ?>
                </th>
                <td>
                    <textarea name="emma_ia_system_prompt" rows="4"
                        class="large-text"><?php echo esc_textarea(get_option('emma_ia_system_prompt')); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('El prompt de sistema usado como fallback si el Assistant ID no está configurado.', 'emma-ia'); ?>
                    </p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php esc_html_e('Nombre del Bot', 'emma-ia'); ?>
                </th>
                <td>
                    <input type="text" name="emma_ia_bot_name"
                        value="<?php echo esc_attr(get_option('emma_ia_bot_name', 'Emma')); ?>" class="regular-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php esc_html_e('URL del Avatar (Opcional)', 'emma-ia'); ?>
                </th>
                <td>
                    <input type="url" name="emma_ia_bot_avatar"
                        value="<?php echo esc_attr(get_option('emma_ia_bot_avatar')); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('Ruta completa a la imagen que deseas usar para el avatar.', 'emma-ia'); ?>
                    </p>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>
    </form>
</div>