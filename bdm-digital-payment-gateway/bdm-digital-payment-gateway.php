<?php
/*
 * Plugin Name: BDM Digital Payment Gateway
 * Plugin URI: https://mercado.dourado.cash/
 * Description: Um plugin para processar pagamentos utilizando BDM Digital. Suporta geração de QR codes, processamento de pagamentos, validação de transações e fornecimento de confirmações. Permite integração com várias carteiras e serviços associados.
 * Version: 0.0.4
 * Author: Dourado Cash
 * Author URI: https://mercado.dourado.cash/
*/

if (!defined('ABSPATH')) {
    exit;
}

// Checagem dos plugins necessários como Woocommerce e Classic Editor

register_activation_hook(__FILE__, 'bdm_install_classic_editor');
function bdm_install_classic_editor() {
    // Checa o woocommerce e dispara erro se não houver e caso haja, ativa.
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('BDM Digital Payment Gateway requer que o WooCommerce esteja instalado e ativado.', 'bdm-digital-payment-gateway'),
            __('Plugin Activation Error', 'bdm-digital-payment-gateway'),
            ['back_link' => true]
        );
    } else {
        activate_plugin('woocommerce/woocommerce.php');
    }

    // Checa o classic editor e dispara erro se não houver e caso haja, ativa.
    if (!is_plugin_active('classic-editor/classic-editor.php')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_installed('classic-editor/classic-editor.php')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('BDM Digital Payment Gateway requer que o Classic Editor esteja instalado e ativado.', 'bdm-digital-payment-gateway'),
                __('Plugin Activation Error', 'bdm-digital-payment-gateway'),
                ['back_link' => true]
            );
        } else {
            activate_plugin('classic-editor/classic-editor.php');
        }
    }

    bdm_create_checkout_page();
}

// Cria uma página checkout personalizada com a correção para o checkout sem blocos como conteúdo ao habilitar o plugin

function bdm_create_checkout_page() {
    $page = array(
        'post_title'    => 'BDM Checkout',
        'post_name'     => 'bdm-checkout',
        'post_content'  => '[woocommerce_checkout]', 
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_author'   => 1,
        'page_template' => 'bdm-checkout-template.php'
    );
    
    if (!get_page_by_path('bdm-checkout')) {
        wp_insert_post($page);

        update_option('woocommerce_checkout_page_id', get_page_by_path('bdm-checkout')->ID);        
    }
}

// Deleta a página criada ao desabilitar o plugin

register_deactivation_hook(__FILE__, 'bdm_remove_checkout_page');
function bdm_remove_checkout_page() {
    $page = get_page_by_path('bdm-checkout');
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}

// Insere URL para a página de configuração na listagem de plugins

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bdm_add_settings_link');
function bdm_add_settings_link($links) {
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=bdm-digital');
    $settings_link = '<a href="' . esc_url($url) . '">' . __('Configurações', 'bdm-digital-payment-gateway') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Registra o template customizado para a página criada

add_filter('theme_page_templates', 'bdm_register_custom_template');
function bdm_register_custom_template($templates) {
    $templates['bdm-checkout-template.php'] = __('BDM Checkout Template', 'bdm-digital-payment-gateway');
    return $templates;
}

// Inclui o template customizado para a checkout criada

add_filter('template_include', 'bdm_load_custom_template');
function bdm_load_custom_template($template) {
    if (is_page('bdm-checkout')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/checkout-template.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}

// Adiciona o plugin como gateway de pagamento

add_filter( 'woocommerce_payment_gateways', 'misha_add_gateway_class' );
function misha_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_BDM_GATEWAY'; // your class name is here
	return $gateways;
}

// Classe do gateway

add_action('plugins_loaded', 'init_gateway_class');
function init_gateway_class() {
    class WC_BDM_GATEWAY extends WC_Payment_Gateway {
        // Declarações
        private $api_key;
        private $endpoint;
        private $partner_email;
        private $sandbox;

        public function __construct() {
            $this->id = 'bdm-digital';
            $this->method_title = __('BDM PIX', 'woocommerce');
            $this->method_description = __('Accept payments via BDM PIX.', 'woocommerce');
            $this->supports = array('products');
        
            $this->init_form_fields();
            $this->init_settings();
        
            $this->title = $this->get_option('title');
            $this->api_key = $this->get_option('api_key') === 'AwXs58ExCGKzK7coV2lw5RqMgETNpg+wplLcKeOPQOR7NhOzEfn/5ca1fGE+6kMw';
            $this->partner_email = $this->get_option('partner_email') === 'wesley.andrade@dourado.tech';
            $this->sandbox = $this->get_option('sandbox') === 'yes';
            $this->enabled = $this->get_option('enabled') === 'yes';
        
            $this->endpoint = $this->sandbox
                ? 'https://sandbox-api.example.com/'
                : 'https://opiihi8ab4.execute-api.us-east-2.amazonaws.com/ecommerce-partner/clients/';
        
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        // Inicia os campos de configuração
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable BDM PIX Payment', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'default' => $this->title,
                ),   
                'method_description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => $this->method_description,
                ),             
                'api_key' => array(
                    'title' => __('API Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your API Key', 'woocommerce'),
                    'default' => 'AwXs58ExCGKzK7coV2lw5RqMgETNpg+wplLcKeOPQOR7NhOzEfn/5ca1fGE+6kMw',
                ),
                'partner_email' => array(
                    'title' => __('Partner Email', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your partner email', 'woocommerce'),
                    'default' => '',
                ),
                'sandbox' => array(
                    'title' => __('Sandbox Mode', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Sandbox Mode', 'woocommerce'),
                    'default' => 'yes',
                    'description' => __('Use the sandbox API endpoint for testing.', 'woocommerce'),
                )
            );
        }

        // Processa o pagamento
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $payload = array(
                'partnerEmail' => $this->partner_email,
                'amount' => (float) $order->get_total(),
                'toAsset' => 'BRL',
                'attachment' => '',
                'fromAsset' => 'BRL'
            );

            $response = wp_remote_post($this->endpoint . 'v33/ecommerce-partner/billing-code', array(
                'method'    => 'POST',
                'body'      => json_encode($payload),
                'headers'   => array(
                    'Content-Type'  => 'application/json',
                    'x-api-key'     => $this->api_key
                ),
                'timeout'   => 60,
            ));

            if (is_wp_error($response)) {
                wc_add_notice(__('Payment error: ', 'woocommerce') . $response->get_error_message(), 'error');
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['QrCode'])) {
                $order->update_status('on-hold', __('Awaiting PIX payment.', 'woocommerce'));
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }
            
            wc_add_notice(__('Payment failed.', 'woocommerce'), 'error');
            return;
        }
    }
}