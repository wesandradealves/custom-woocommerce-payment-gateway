<?php
/*
 * Plugin Name: BDM Digital Payment Gateway
 * Plugin URI: https://mercado.dourado.cash/
 * Description: Um plugin para processar pagamentos utilizando BDM Digital. Suporta geração de QR codes, processamento de pagamentos, validação de transações e fornecimento de confirmações. Permite integração com várias carteiras e serviços associados.
 * Version: 0.0.4
 * Author: Dourado Cash
 * Author URI: https://mercado.dourado.cash/
 */

// Definir constantes
define('BDM_PAYMENT_GATEWAY_OPTION_KEY', 'bdm_payment_gateway_options');
define('BDM_PAYMENT_GATEWAY_MENU_SLUG', 'bdm-payment-gateway-settings');
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

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
            plugin_dir_url(__FILE__) . 'assets/css/style.css'
        );
    }
}

// Renderizar a página de configurações
function bdm_render_settings_page() {
    ?>
    <section id="bdm-settings-page" class="p-5 animate__animated animate__fadeIn">
        <header class="d-block mb-4">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/img/logo.jpg'; ?>" alt="BDM Digital" />
        </header>
        <form method="post" action="options.php" class="bdm-settings-form">
            <?php
            settings_fields(BDM_PAYMENT_GATEWAY_OPTION_KEY);
            do_settings_sections(BDM_PAYMENT_GATEWAY_MENU_SLUG);
            submit_button(__('Salvar Configurações', 'bdm-digital-payment-gateway'), 'primary large');
            ?>
        </form>
    </section>
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
    echo '<p>' . __('Insira os detalhes necessários para integração com o gateway de pagamento BDM Digital.<br/>Todos os campos são obrigatórios.', 'bdm-digital-payment-gateway') . '</p>';
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

// Register Template

add_filter('theme_page_templates', 'bdm_register_custom_template');
function bdm_register_custom_template($templates) {
    $templates['bdm-checkout-template.php'] = __('BDM Checkout Template', 'bdm-digital-payment-gateway');
    return $templates;
}

add_filter('template_include', 'bdm_load_custom_template');
function bdm_load_custom_template($template) {
    if (is_page('checkout')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/checkout-template.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}


// Hook into plugin activation
register_activation_hook(__FILE__, 'bdm_create_checkout_page');

function bdm_create_checkout_page() {
    $checkout_page = [
        'post_title'    => 'Checkout',
        'post_content'  => '',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_name'     => 'checkout',
    ];

    $existing_page = get_page_by_path('checkout');
    if (!$existing_page) {
        $post_id = wp_insert_post($checkout_page);
        if ($post_id) {
            update_post_meta($post_id, '_wp_page_template', 'bdm-checkout-template.php');
        }
    }
}

register_uninstall_hook(__FILE__, 'bdm_remove_checkout_page');

function bdm_remove_checkout_page() {
    $checkout_page = get_page_by_path('checkout');
    if ($checkout_page) {
        wp_delete_post($checkout_page->ID, true);
    }
}

add_action('wp_enqueue_scripts', 'bdm_enqueue_checkout_scripts');

// Enqueue Scripts

function bdm_enqueue_checkout_scripts() {
    if (is_page('checkout')) {
        wp_enqueue_script(
            'bdm-checkout-app',
            plugin_dir_url(__FILE__) . 'assets/js/app.js',
            [],
            '1.0.0',
            true
        );
    }
}