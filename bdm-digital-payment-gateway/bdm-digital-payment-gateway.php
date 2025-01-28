<?php
/*
 * Plugin Name: WooCommerce BDM Digital Payment Gateway
 * Plugin URI: https://mercado.dourado.cash/
 * Description: Um plugin para processar pagamentos utilizando BDM Digital. Suporta geração de QR codes, processamento de pagamentos, validação de transações e fornecimento de confirmações. Permite integração com várias carteiras e serviços associados.
 * Version: 0.0.4
 * Author: Dourado Cash
 * Author URI: https://mercado.dourado.cash/
 */

// Definir constantes
define('BDM_PAYMENT_GATEWAY_OPTION_KEY', 'bdm_payment_gateway_options');
define('BDM_PAYMENT_GATEWAY_MENU_SLUG', 'bdm-payment-gateway-settings');

// Registrar menu de administração
add_action('admin_menu', 'bdm_register_settings_page');
function bdm_register_settings_page() {
    add_options_page(
        __('Configurações do BDM Digital Payment Gateway', 'bdm-digital-payment-gateway'), 
        __('BDM Digital Payment Gateway', 'bdm-digital-payment-gateway'), 
        'manage_options', 
        BDM_PAYMENT_GATEWAY_MENU_SLUG, 
        'bdm_render_settings_page'
    );
}

// Carregar estilos personalizados
add_action('admin_enqueue_scripts', 'bdm_enqueue_admin_styles');
function bdm_enqueue_admin_styles($hook) {
    if ($hook === 'settings_page_' . BDM_PAYMENT_GATEWAY_MENU_SLUG) {
        wp_enqueue_style(
            'bdm-admin-styles',
            plugin_dir_url(__FILE__) . 'css/admin-styles.css'
        );
    }
}

// Renderizar a página de configurações
function bdm_render_settings_page() {
    ?>
    <div class="wrap bdm-settings-page">
        <h1 class="bdm-page-title"><?php esc_html_e('Configurações do BDM Digital Payment Gateway', 'bdm-digital-payment-gateway'); ?></h1>
        <p class="bdm-description">
            <?php esc_html_e('Configure os detalhes necessários para integrar o gateway de pagamento BDM Digital.', 'bdm-digital-payment-gateway'); ?>
        </p>
        <form method="post" action="options.php" class="bdm-settings-form">
            <?php
            settings_fields(BDM_PAYMENT_GATEWAY_OPTION_KEY);
            do_settings_sections(BDM_PAYMENT_GATEWAY_MENU_SLUG);
            submit_button(__('Salvar Configurações', 'bdm-digital-payment-gateway'), 'primary large');
            ?>
        </form>
    </div>
    <?php
}

// Inicializar configurações do plugin
add_action('admin_init', 'bdm_initialize_settings');
function bdm_initialize_settings() {
    register_setting(
        BDM_PAYMENT_GATEWAY_OPTION_KEY,
        BDM_PAYMENT_GATEWAY_OPTION_KEY,
        'bdm_validate_settings'
    );

    add_settings_section(
        'bdm_settings_section', 
        __('Configuração do Gateway de Pagamento', 'bdm-digital-payment-gateway'), 
        'bdm_section_description', 
        BDM_PAYMENT_GATEWAY_MENU_SLUG
    );

    $fields = [
        'bdm_access_token' => __('Token de Acesso', 'bdm-digital-payment-gateway'),
        'bdm_webhook_url' => __('URL do Webhook', 'bdm-digital-payment-gateway'),
        'bdm_base_endpoint' => __('Endpoint Base', 'bdm-digital-payment-gateway'),
        'bdm_wallet_email' => __('E-mail da Carteira', 'bdm-digital-payment-gateway'),
    ];

    foreach ($fields as $field_id => $field_label) {
        add_settings_field(
            $field_id,
            $field_label,
            'bdm_render_field',
            BDM_PAYMENT_GATEWAY_MENU_SLUG,
            'bdm_settings_section',
            ['id' => $field_id]
        );
    }
}

// Descrição da seção
function bdm_section_description() {
    echo '<p>' . __('Insira os detalhes necessários para integração com o gateway de pagamento BDM Digital. Todos os campos são obrigatórios.', 'bdm-digital-payment-gateway') . '</p>';
}

// Sanitizar e validar configurações
function bdm_validate_settings($input) {
    $sanitized = [];
    $required_fields = [
        'bdm_access_token' => __('Token de Acesso', 'bdm-digital-payment-gateway'),
        'bdm_webhook_url' => __('URL do Webhook', 'bdm-digital-payment-gateway'),
        'bdm_base_endpoint' => __('Endpoint Base', 'bdm-digital-payment-gateway'),
        'bdm_wallet_email' => __('E-mail da Carteira', 'bdm-digital-payment-gateway'),
    ];

    foreach ($required_fields as $field_id => $field_label) {
        if (empty($input[$field_id])) {
            add_settings_error(
                BDM_PAYMENT_GATEWAY_OPTION_KEY,
                $field_id,
                sprintf(__('O campo %s é obrigatório.', 'bdm-digital-payment-gateway'), $field_label),
                'error'
            );
        } else {
            switch ($field_id) {
                case 'bdm_webhook_url':
                case 'bdm_base_endpoint':
                    $sanitized[$field_id] = esc_url_raw($input[$field_id]);
                    break;
                case 'bdm_wallet_email':
                    $sanitized[$field_id] = sanitize_email($input[$field_id]);
                    break;
                default:
                    $sanitized[$field_id] = sanitize_text_field($input[$field_id]);
            }
        }
    }

    return $sanitized;
}

// Renderizar campos individuais
function bdm_render_field($args) {
    $options = get_option(BDM_PAYMENT_GATEWAY_OPTION_KEY, []);
    $value = $options[$args['id']] ?? '';
    $type = match ($args['id']) {
        'bdm_webhook_url', 'bdm_base_endpoint' => 'url',
        'bdm_wallet_email' => 'email',
        default => 'text',
    };
    printf(
        '<input type="%1$s" id="%2$s" name="%3$s[%2$s]" value="%4$s" class="regular-text" required>',
        esc_attr($type),
        esc_attr($args['id']),
        BDM_PAYMENT_GATEWAY_OPTION_KEY,
        esc_attr($value)
    );
}

// Adicionar link de configurações na lista de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bdm_add_settings_link');
function bdm_add_settings_link($links) {
    $url = admin_url('options-general.php?page=' . BDM_PAYMENT_GATEWAY_MENU_SLUG);
    $settings_link = '<a href="' . esc_url($url) . '">' . __('Configurações', 'bdm-digital-payment-gateway') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}