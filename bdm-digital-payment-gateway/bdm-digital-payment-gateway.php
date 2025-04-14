<?php
/*
 * Plugin Name: BDM Digital Payment Gateway
 * Plugin URI: https://mercado.dourado.cash/
 * Description: Um plugin para processar pagamentos utilizando BDM Digital. Suporta geração de QR codes, processamento de pagamentos, validação de transações e fornecimento de confirmações. Permite integração com várias carteiras e serviços associados.
 * Version: 1.0.0
 * Author: Dourado Cash
 * Author URI: https://mercado.dourado.cash/
*/

if (!defined('ABSPATH')) {
    exit;
}

$storefront_theme = wp_get_theme('storefront');

if ($storefront_theme->exists()) {
    switch_theme('storefront');
} 

register_activation_hook(__FILE__, 'bdm_install_classic_editor');
function bdm_install_classic_editor() {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));

        wp_die(
            __('BDM Digital Payment Gateway requer que o WooCommerce esteja instalado e ativado.', 'bdm-digital-payment-gateway'),
            __('Plugin Activation Error', 'bdm-digital-payment-gateway'),
            ['back_link' => true]
        );
    } else {
        bdm_create_checkout_page();
    }
}

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

register_deactivation_hook(__FILE__, 'bdm_remove_checkout_page');
function bdm_remove_checkout_page() {
    $page = get_page_by_path('bdm-checkout');
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bdm_add_settings_link');
function bdm_add_settings_link($links) {
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=bdm-digital');
    $settings_link = '<a href="' . esc_url($url) . '">' . __('Configurações', 'bdm-digital-payment-gateway') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_filter('theme_page_templates', 'bdm_register_custom_template');
function bdm_register_custom_template($templates) {
    $templates['bdm-checkout-template.php'] = __('BDM Checkout Template', 'bdm-digital-payment-gateway');
    return $templates;
}

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

add_filter( 'woocommerce_payment_gateways', 'add_gateway' );
function add_gateway( $gateways ) {
	$gateways[] = 'WC_BDM_GATEWAY'; 
	return $gateways;
}

function bdm_enqueue_scripts() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    if (is_checkout() || is_page('bdm-checkout')) {
        wp_enqueue_style(
            'bdm-checkout-style',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            array(),
            '1.0.0', 
            'all' 
        );

        wp_enqueue_style(
            'bootstrap',
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css',
            array(), 
            '1.0.0',
            'all' 
        );        

        wp_enqueue_script(
            'bdm-checkout-js',
            plugin_dir_url(__FILE__) . 'assets/js/main.js',
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/main.js'),
            true
        );

        wp_enqueue_script(
            'toast-js',
            '//cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js',
            array('jquery'),
            '1.3.2',
            true
        );

        wp_enqueue_style(
            'toast-css',
            '//cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css',
            array(),
            '1.0.0', 
            'all' 
        );        

        $settings = get_option('woocommerce_bdm-digital_settings'); 

        $checkout_data = array(
            'products' => array_map(function($item) {
                return array(
                    'id' => $item['product_id'],
                    'title' => $item['data']->get_name(),
                    'quantity' => $item['quantity'],
                    'price' => wc_price($item['line_total'])
                );
            }, WC()->cart->get_cart()),
            'settings' => array(
                'api_key' => $settings['api_key'] ?? '',
                'endpoint' => $settings['sandbox']
                ? 'https://opiihi8ab4.execute-api.us-east-2.amazonaws.com/'
                : 'https://partner.dourado.cash/',
                'asset' => $settings['asset'] ?? '',
                'partner_email' => $settings['partner_email'] ?? '',
                'sandbox' => $settings['sandbox'] ?? '',
                'consumer_key' => $settings['rest_key'] ?? '',
                'endpoint_quotation' => $settings['endpoint_quotation'] ?? '',
                'consumer_secret' => $settings['rest_secret'] ?? ''
            )
        );

        wp_localize_script('bdm-checkout-js', 'bdm_checkout_data', $checkout_data);
    }
}
add_action('wp_enqueue_scripts', 'bdm_enqueue_scripts');
add_action('wp_ajax_create_bdm_order', 'create_bdm_order');
add_action('wp_ajax_nopriv_create_bdm_order', 'create_bdm_order'); 

function create_bdm_order() {
    if (!isset($_POST['billing_code'], $_POST['amount'], $_POST['partner_email'], $_POST['products'])) {
        wp_send_json_error(['message' => 'Missing required parameters.']);
    }

    $billing_code = sanitize_text_field($_POST['billing_code']);
    $amount = floatval($_POST['amount']);
    $partner_email = sanitize_email($_POST['partner_email']);
    $products = $_POST['products']; 

    $order = wc_create_order();

    foreach ($products as $product_data) {
        $product_id = $product_data['id'];
        $quantity = $product_data['quantity'];

        $product = wc_get_product($product_id);
        if ($product) {
            $order->add_product($product, $quantity);
        }
    }

    $order->set_billing_email($partner_email);
    $order->set_billing_address_1('');
    $order->set_billing_city('');
    $order->set_billing_postcode(''); 

    $order->set_total($amount);

    $order->update_meta_data('_billing_code', $billing_code);

    $order->save();

    wp_send_json_success(['order_id' => $order->get_id()]);
}

add_action('plugins_loaded', 'init_gateway_class');
function init_gateway_class() {
    class WC_BDM_GATEWAY extends WC_Payment_Gateway {
        private $api_key;
        private $endpoint;
        private $partner_email;
        private $sandbox;
        private $asset;
        private $rest_key;
        private $rest_secret;
        private $endpoint_quotation; 

        public function __construct() {
            $this->id = 'bdm-digital';
            $this->method_title = __('BDM Digital', 'woocommerce');
            $this->method_description = __('Accept payments via BDM Digital.', 'woocommerce');
            $this->supports = array('products');
        
            $this->init_form_fields();
            $this->init_settings();
        
            $this->title = $this->get_option('title');
            $this->api_key = $this->get_option('api_key') === 'AwXs58ExCGKzK7coV2lw5RqMgETNpg+wplLcKeOPQOR7NhOzEfn/5ca1fGE+6kMw';
            $this->partner_email = $this->get_option('partner_email') === 'wesley.andrade@dourado.tech';
            $this->sandbox = $this->get_option('sandbox') === 'yes';
            $this->asset = $this->get_option('asset') === 'BDM';
            $this->rest_key = $this->get_option('rest_key') === 'ck_3e5bb37ed71eb20d4071722b9c26a171131a29f9';
            $this->rest_secret = $this->get_option('rest_secret') === 'cs_3deba108ca46008b9a07a0f918b03ca9b7b90ee6';
            $this->endpoint_quotation = $this->get_option('endpoint_quotation') === 'https://opiihi8ab4.execute-api.us-east-2.amazonaws.com/ecommerce-partner/clients/quotation/all/BDM';
        
            $this->endpoint = $this->sandbox
                ? 'https://opiihi8ab4.execute-api.us-east-2.amazonaws.com/'
                : 'https://api.example.com/';
        
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable BDM Digital Payment', 'woocommerce'),
                    'default' => ''
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'default' => $this->method_title,
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
                    'default' => $this->get_option('api_key') ?? '',
                ),
                'partner_email' => array(
                    'title' => __('Partner Email', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your partner email', 'woocommerce'),
                    'default' => $this->get_option('partner_email') ?? '',
                ),
                'asset' => array(
                    'title' => __('Asset', 'woocommerce'),
                    'type' => 'text',
                    'default' => $this->get_option('asset') ?? ''
                ),  
                'sandbox' => array(
                    'title' => __('Sandbox Mode', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Sandbox Mode', 'woocommerce'),
                    'default' => $this->get_option('sandbox') ?? '',
                    'description' => __('Use the sandbox API endpoint for testing.', 'woocommerce'),
                ),  
                'rest_key' => array(
                    'title' => __('Rest API Key', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Rest API Key', 'woocommerce'),
                    'default' => $this->get_option('rest_key') ?? ''
                ),  
                'rest_secret' => array(
                    'title' => __('Rest API Secret', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Rest API Secret', 'woocommerce'),
                    'default' => $this->get_option('rest_secret') ?? ''
                ), 
                'endpoint_quotation' => array(
                    'title' => __('Cotation Endpoint', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Cotation Endpoint', 'woocommerce'),
                    'default' => $this->get_option('endpoint_quotation') ?? ''
                ), 
            );
        }
    }
}